<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookCandidateResponseEnum;
use App\Enums\BookCandidateStatusEnum;
use App\Enums\MemberBookQueueItemStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Events\BookCandidateAwaitingConfirmation;
use App\Events\BookCandidateConfirmed;
use App\Events\BookCandidateProposed;
use App\Events\BookCandidateRejected;
use App\Events\BookCandidateReplaced;
use App\Models\BookCandidate;
use App\Models\BookCandidateResponse;
use App\Models\ClubMember;
use App\Models\MemberBookQueueItem;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class BookSelectionStateMachine
{
    public function __construct(
        private readonly TurnOrderService $turnOrder,
        private readonly MemberBookQueueService $bookQueue,
    ) {
    }

    public function createCandidateForCurrentSelector(int $clubId): ?BookCandidate
    {
        return DB::transaction(function () use ($clubId): ?BookCandidate {
            if ($this->activeCandidate($clubId)) {
                return null;
            }

            $cycle = $this->openCycle($clubId);
            if ($cycle) {
                if ($cycle->status === ReadingCycleStatusEnum::Active) {
                    return null;
                }

                $hasActive = $cycle->bookCandidate()
                    ->whereIn('status', [
                        BookCandidateStatusEnum::Pending->value,
                        BookCandidateStatusEnum::AwaitingOwnerConfirmation->value,
                    ])
                    ->exists();

                if ($hasActive) {
                    return null;
                }

                $cycle->delete();
            }

            $selector = $this->turnOrder->currentSelector($clubId);
            if (! $selector) {
                return null;
            }

            return $this->createCandidateFromNextQueuedItem($selector);
        });
    }

    public function recalculateCandidate(BookCandidate $candidate): BookCandidate
    {
        return DB::transaction(function () use ($candidate): BookCandidate {
            $candidate->loadMissing('proposer', 'queueItem', 'responses', 'readingCycle');
            $responses = $this->activeResponses($candidate);

            if ($responses->contains(fn (BookCandidateResponseEnum $response) => $response === BookCandidateResponseEnum::Read)) {
                $candidate->update(['status' => BookCandidateStatusEnum::Rejected]);
                if ($candidate->queueItem) {
                    $this->bookQueue->removeFromLiveQueue($candidate->queueItem, MemberBookQueueItemStatusEnum::Rejected);
                }

                DB::afterCommit(fn () => event(new BookCandidateRejected($candidate->refresh())));

                $next = $this->createCandidateFromNextQueuedItem($candidate->proposer, $candidate->readingCycle);

                if ($next === null) {
                    $cycle = $candidate->readingCycle;
                    if ($cycle && $cycle->status === ReadingCycleStatusEnum::Proposed) {
                        $cycle->delete();
                    }
                }

                return $candidate->refresh();
            }

            $activeMemberCount = ClubMember::query()
                ->where('club_id', $candidate->proposer->club_id)
                ->where('is_active', true)
                ->count();

            $allNotRead = $responses->count() === $activeMemberCount
                && $responses->every(fn (BookCandidateResponseEnum $response) => $response === BookCandidateResponseEnum::NotRead);

            if ($allNotRead) {
                $candidate->update(['status' => BookCandidateStatusEnum::AwaitingOwnerConfirmation]);
                DB::afterCommit(fn () => event(new BookCandidateAwaitingConfirmation($candidate->refresh())));
            }

            return $candidate->refresh();
        });
    }

    public function confirmCandidate(BookCandidate $candidate): BookCandidate
    {
        return DB::transaction(function () use ($candidate): BookCandidate {
            $candidate->loadMissing('proposer', 'queueItem', 'responses', 'readingCycle');

            if ($candidate->status !== BookCandidateStatusEnum::AwaitingOwnerConfirmation) {
                abort(422, 'Кандидата можно подтвердить только после ответов not_read от всех активных участников.');
            }

            if ($this->activeCycle($candidate->proposer->club_id)) {
                abort(422, 'Новый цикл нельзя начать, пока текущий цикл активен.');
            }

            $cycle = $candidate->readingCycle;
            if (! $cycle) {
                $cycle = ReadingCycle::create([
                    'club_id' => $candidate->proposer->club_id,
                    'book_id' => $candidate->book_id,
                    'proposer_id' => $candidate->proposer_id,
                    'cycle_number' => $this->nextCycleNumber($candidate->proposer->club_id),
                    'status' => ReadingCycleStatusEnum::Proposed,
                ]);
            }

            abort_if($cycle->status !== ReadingCycleStatusEnum::Proposed, 422, 'Подтвердить можно только предложенный цикл.');

            $cycle->update([
                'book_id' => $candidate->book_id,
                'proposer_id' => $candidate->proposer_id,
                'status' => ReadingCycleStatusEnum::Active,
            ]);

            $candidate->update([
                'reading_cycle_id' => $cycle->id,
                'status' => BookCandidateStatusEnum::Approved,
            ]);
            if ($candidate->queueItem) {
                $this->bookQueue->removeFromLiveQueue($candidate->queueItem, MemberBookQueueItemStatusEnum::Approved);
            }

            ClubMember::query()
                ->where('club_id', $candidate->proposer->club_id)
                ->where('is_active', true)
                ->get()
                ->each(fn (ClubMember $member) => ReadingProgress::create([
                    'reading_cycle_id' => $cycle->id,
                    'club_member_id' => $member->id,
                    'status' => ReadingProgressStatusEnum::NotStarted,
                ]));

            DB::afterCommit(fn () => event(new BookCandidateConfirmed($candidate->refresh())));

            return $candidate->refresh();
        });
    }

    public function syncPendingCandidateWithQueueHead(ClubMember $selector): ?BookCandidate
    {
        return DB::transaction(function () use ($selector): ?BookCandidate {
            $candidate = $this->activeCandidate($selector->club_id);
            $head = $this->bookQueue->orderedLiveItems($selector)->first();

            if (! $candidate) {
                if ($this->activeCycle($selector->club_id)) {
                    return null;
                }

                return $this->createCandidateFromNextQueuedItem($selector);
            }

            if ($candidate->proposer_id !== $selector->id) {
                return $candidate;
            }

            if ($candidate->status === BookCandidateStatusEnum::AwaitingOwnerConfirmation) {
                return $candidate;
            }

            if (! $head || $candidate->member_book_queue_item_id === $head->id) {
                return $candidate;
            }

            if ($head->status !== MemberBookQueueItemStatusEnum::Queued) {
                return $candidate;
            }

            return $this->replacePendingCandidate($candidate, $head);
        });
    }

    public function makeQueueItemCandidate(MemberBookQueueItem $item): BookCandidate
    {
        return DB::transaction(function () use ($item): BookCandidate {
            $item->loadMissing('clubMember');
            $candidate = $this->activeCandidate($item->clubMember->club_id);

            abort_if(
                $candidate?->status === BookCandidateStatusEnum::AwaitingOwnerConfirmation,
                422,
                'Книгу нельзя заменить после завершения проверки.',
            );

            if (! $candidate) {
                abort_if($this->activeCycle($item->clubMember->club_id), 422, 'Новый цикл нельзя начать, пока текущий цикл активен.');

                $created = $this->createCandidateFromNextQueuedItem($item->clubMember);
                abort_if(! $created, 422, 'Не удалось создать кандидата из очереди.');

                return $created;
            }

            abort_if($candidate->proposer_id !== $item->club_member_id, 403, 'Нельзя менять чужого кандидата.');

            if ($candidate->member_book_queue_item_id === $item->id) {
                return $candidate;
            }

            return $this->replacePendingCandidate($candidate, $item);
        });
    }

    public function nextSelectorHasQueuedBooks(int $clubId): bool
    {
        $selector = $this->turnOrder->nextSelector($clubId);

        return $selector ? $this->bookQueue->headQueuedItem($selector) !== null : false;
    }

    private function createCandidateFromNextQueuedItem(ClubMember $selector, ?ReadingCycle $cycle = null): ?BookCandidate
    {
        if ($this->activeCandidate($selector->club_id)) {
            return null;
        }

        $item = $this->bookQueue->headQueuedItem($selector);

        if (! $item) {
            return null;
        }

        $item->update(['status' => MemberBookQueueItemStatusEnum::InVerification]);

        $cycle ??= ReadingCycle::create([
            'club_id' => $selector->club_id,
            'book_id' => $item->book_id,
            'proposer_id' => $selector->id,
            'cycle_number' => $this->nextCycleNumber($selector->club_id),
            'status' => ReadingCycleStatusEnum::Proposed,
        ]);

        $cycle->update([
            'book_id' => $item->book_id,
            'proposer_id' => $selector->id,
            'status' => ReadingCycleStatusEnum::Proposed,
        ]);

        $candidate = BookCandidate::create([
            'book_id' => $item->book_id,
            'proposer_id' => $selector->id,
            'reading_cycle_id' => $cycle->id,
            'member_book_queue_item_id' => $item->id,
            'description' => $item->description,
            'status' => BookCandidateStatusEnum::Pending,
        ]);

        ClubMember::query()
            ->where('club_id', $selector->club_id)
            ->where('is_active', true)
            ->get()
            ->each(fn (ClubMember $member) => BookCandidateResponse::create([
                'book_candidate_id' => $candidate->id,
                'club_member_id'    => $member->id,
                'response'          => $member->id === $selector->id
                    ? BookCandidateResponseEnum::NotRead
                    : BookCandidateResponseEnum::Pending,
            ]));

        DB::afterCommit(fn () => event(new BookCandidateProposed($candidate)));

        return $candidate;
    }

    private function replacePendingCandidate(BookCandidate $candidate, MemberBookQueueItem $item): BookCandidate
    {
        abort_if($candidate->status !== BookCandidateStatusEnum::Pending, 422, 'Заменить можно только кандидата в проверке.');

        $candidate->loadMissing('proposer', 'queueItem', 'readingCycle');
        $item->loadMissing('book');

        $candidate->responses()->delete();
        $candidate->update(['status' => BookCandidateStatusEnum::Rejected]);
        $candidate->queueItem?->update(['status' => MemberBookQueueItemStatusEnum::Queued]);

        $item->update(['status' => MemberBookQueueItemStatusEnum::InVerification]);

        $cycle = $candidate->readingCycle;
        if (! $cycle) {
            $cycle = ReadingCycle::create([
                'club_id' => $candidate->proposer->club_id,
                'book_id' => $item->book_id,
                'proposer_id' => $candidate->proposer_id,
                'cycle_number' => $this->nextCycleNumber($candidate->proposer->club_id),
                'status' => ReadingCycleStatusEnum::Proposed,
            ]);
        }

        $cycle->update([
            'book_id' => $item->book_id,
            'proposer_id' => $candidate->proposer_id,
            'status' => ReadingCycleStatusEnum::Proposed,
        ]);

        $next = BookCandidate::create([
            'book_id' => $item->book_id,
            'proposer_id' => $candidate->proposer_id,
            'reading_cycle_id' => $cycle->id,
            'member_book_queue_item_id' => $item->id,
            'description' => $item->description,
            'status' => BookCandidateStatusEnum::Pending,
        ]);

        ClubMember::query()
            ->where('club_id', $candidate->proposer->club_id)
            ->where('is_active', true)
            ->get()
            ->each(fn (ClubMember $member) => BookCandidateResponse::create([
                'book_candidate_id' => $next->id,
                'club_member_id'    => $member->id,
                'response'          => $member->id === $candidate->proposer_id
                    ? BookCandidateResponseEnum::NotRead
                    : BookCandidateResponseEnum::Pending,
            ]));

        DB::afterCommit(fn () => event(new BookCandidateReplaced($next)));

        return $next;
    }

    private function activeResponses(BookCandidate $candidate): Collection
    {
        $activeMemberIds = ClubMember::where('club_id', $candidate->proposer->club_id)
            ->where('is_active', true)
            ->pluck('id');

        return $candidate->responses()
            ->whereIn('club_member_id', $activeMemberIds)
            ->get()
            ->pluck('response', 'club_member_id');
    }

    private function nextCycleNumber(int $clubId): int
    {
        return (int) ReadingCycle::where('club_id', $clubId)->max('cycle_number') + 1;
    }

    private function activeCandidate(int $clubId): ?BookCandidate
    {
        return BookCandidate::query()
            ->whereIn('status', [
                BookCandidateStatusEnum::Pending->value,
                BookCandidateStatusEnum::AwaitingOwnerConfirmation->value,
            ])
            ->whereHas('proposer', fn ($query) => $query->where('club_id', $clubId))
            ->first();
    }

    private function openCycle(int $clubId): ?ReadingCycle
    {
        return ReadingCycle::where('club_id', $clubId)
            ->whereIn('status', [
                ReadingCycleStatusEnum::Proposed->value,
                ReadingCycleStatusEnum::Active->value,
            ])
            ->first();
    }

    private function activeCycle(int $clubId): ?ReadingCycle
    {
        return ReadingCycle::where('club_id', $clubId)
            ->where('status', ReadingCycleStatusEnum::Active->value)
            ->first();
    }
}
