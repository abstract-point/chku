<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookCandidateResponseEnum;
use App\Enums\BookCandidateStatusEnum;
use App\Enums\MemberBookQueueItemStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Models\BookCandidate;
use App\Models\BookCandidateResponse;
use App\Models\ClubMember;
use App\Models\MemberBookQueueItem;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use App\Models\TurnOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class BookSelectionStateMachine
{
    public function createCandidateFromNextSelector(int $clubId): ?BookCandidate
    {
        return DB::transaction(function () use ($clubId): ?BookCandidate {
            if ($this->activeCandidate($clubId) || $this->activeCycle($clubId)) {
                return null;
            }

            $selector = $this->nextSelector($clubId);
            if (! $selector) {
                return null;
            }

            return $this->createCandidateFromNextQueuedItem($selector);
        });
    }

    public function recalculateCandidate(BookCandidate $candidate): BookCandidate
    {
        return DB::transaction(function () use ($candidate): BookCandidate {
            $candidate->loadMissing('proposer', 'queueItem', 'responses');
            $responses = $this->activeResponses($candidate);

            if ($responses->contains(fn (BookCandidateResponseEnum $response) => $response === BookCandidateResponseEnum::Read)) {
                $candidate->update(['status' => BookCandidateStatusEnum::Rejected]);
                $candidate->queueItem?->update(['status' => MemberBookQueueItemStatusEnum::Rejected]);
                $this->createCandidateFromNextQueuedItem($candidate->proposer);

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
            }

            return $candidate->refresh();
        });
    }

    public function confirmCandidate(BookCandidate $candidate): BookCandidate
    {
        return DB::transaction(function () use ($candidate): BookCandidate {
            $candidate->loadMissing('proposer', 'queueItem', 'responses');

            if ($candidate->status !== BookCandidateStatusEnum::AwaitingOwnerConfirmation) {
                abort(422, 'Кандидата можно подтвердить только после ответов not_read от всех активных участников.');
            }

            if ($this->activeCycle($candidate->proposer->club_id)) {
                abort(422, 'Новый цикл нельзя начать, пока текущий цикл активен.');
            }

            $cycle = ReadingCycle::create([
                'club_id' => $candidate->proposer->club_id,
                'book_id' => $candidate->book_id,
                'proposer_id' => $candidate->proposer_id,
                'cycle_number' => (int) ReadingCycle::max('cycle_number') + 1,
                'status' => ReadingCycleStatusEnum::Active,
            ]);

            $candidate->update([
                'reading_cycle_id' => $cycle->id,
                'status' => BookCandidateStatusEnum::Approved,
            ]);
            $candidate->queueItem?->update(['status' => MemberBookQueueItemStatusEnum::Approved]);

            ClubMember::query()
                ->where('club_id', $candidate->proposer->club_id)
                ->where('is_active', true)
                ->get()
                ->each(fn (ClubMember $member) => ReadingProgress::create([
                    'reading_cycle_id' => $cycle->id,
                    'club_member_id' => $member->id,
                    'status' => ReadingProgressStatusEnum::NotStarted,
                ]));

            $this->advanceTurnOrder($candidate->proposer);

            return $candidate->refresh();
        });
    }

    public function nextSelector(int $clubId): ?ClubMember
    {
        return TurnOrder::with('clubMember.user')
            ->where('club_id', $clubId)
            ->where('is_next', true)
            ->first()
            ?->clubMember;
    }

    public function nextSelectorHasQueuedBooks(int $clubId): bool
    {
        $selector = $this->nextSelector($clubId);

        return $selector
            ? $selector->bookQueueItems()->where('status', MemberBookQueueItemStatusEnum::Queued->value)->exists()
            : false;
    }

    private function createCandidateFromNextQueuedItem(ClubMember $selector): ?BookCandidate
    {
        if ($this->activeCandidate($selector->club_id)) {
            return null;
        }

        $item = MemberBookQueueItem::with('book')
            ->where('club_member_id', $selector->id)
            ->where('status', MemberBookQueueItemStatusEnum::Queued->value)
            ->orderBy('position')
            ->first();

        if (! $item) {
            return null;
        }

        $item->update(['status' => MemberBookQueueItemStatusEnum::InVerification]);

        $candidate = BookCandidate::create([
            'book_id' => $item->book_id,
            'proposer_id' => $selector->id,
            'member_book_queue_item_id' => $item->id,
            'reason' => $item->reason,
            'description' => $item->description,
            'status' => BookCandidateStatusEnum::Pending,
        ]);

        ClubMember::query()
            ->where('club_id', $selector->club_id)
            ->where('is_active', true)
            ->get()
            ->each(fn (ClubMember $member) => BookCandidateResponse::create([
                'book_candidate_id' => $candidate->id,
                'club_member_id' => $member->id,
                'response' => BookCandidateResponseEnum::Pending,
            ]));

        return $candidate;
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

    private function activeCycle(int $clubId): ?ReadingCycle
    {
        return ReadingCycle::where('club_id', $clubId)
            ->where('status', ReadingCycleStatusEnum::Active->value)
            ->first();
    }

    private function advanceTurnOrder(ClubMember $proposer): void
    {
        $orders = TurnOrder::query()
            ->where('club_id', $proposer->club_id)
            ->orderBy('position')
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        $currentIndex = $orders->search(fn (TurnOrder $order) => $order->club_member_id === $proposer->id);
        if ($currentIndex === false) {
            return;
        }

        $nextIndex = ((int) $currentIndex + 1) % $orders->count();

        foreach ($orders as $index => $order) {
            $order->update([
                'is_current' => $index === $currentIndex,
                'is_next' => $index === $nextIndex,
            ]);
        }
    }
}
