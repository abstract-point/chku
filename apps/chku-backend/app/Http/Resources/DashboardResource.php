<?php

namespace App\Http\Resources;

use App\DTOs\DashboardData;
use App\Enums\MeetingRsvpStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Models\Meeting;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use App\Support\MemberAvatar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property DashboardData $resource
 */
class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentCycle = $this->resource->currentCycle;
        $book = $currentCycle?->book;
        $progress = $this->resource->currentUserProgress;
        $nextSelector = $this->resource->nextSelector;
        $isNextSelector = $nextSelector?->id === $this->resource->currentMember->id;
        $activeCandidateProposerId = $this->resource->activeCandidate?->proposer_id;
        $proposedCycle = $this->resource->activeCandidate?->readingCycle;
        $owlMedalsByMemberId = $this->owlMedalsByMemberId($currentCycle, $this->resource->nextMeeting);

        return [
            'club' => new ClubResource($this->resource->club),
            'currentBook' => $book ? [
                'title' => $book->title,
                'coverTitle' => $book->title,
                'author' => $book->author,
                'selectedBy' => $currentCycle->proposer?->user?->name,
                'description' => $book->description,
                'coverUrl' => $book->cover_url,
                'coverColor' => $book->cover_color,
                'genres' => GenreResource::collection($book->genres ?? collect()),
                'progress' => $progress?->progress_percent ?? 0,
                'progressLabel' => $this->formatProgressLabel($progress),
                'cycleNumber' => $currentCycle->cycle_number,
                'cycleStatus' => $currentCycle->status->value,
                'canEditBook' => $this->canEditBook($request, $currentCycle),
            ] : null,
            'memberProgress' => $this->resource->memberProgress
                ?->sort($this->sortProgress(...))
                ->values()
                ->map(fn ($p) => [
                    'id' => $p->club_member_id,
                    'name' => $p->clubMember?->user?->name,
                    'avatarUrl' => MemberAvatar::url($p->clubMember),
                    'status' => match ($p->status->value) {
                        'finished' => 'Закончила',
                        'not_started' => 'Не начал',
                        default => null,
                    },
                    'progress' => $p->progress_percent,
                    'badge' => $p->status->value === 'reading' ? 'Читает' : null,
                    'medal' => $owlMedalsByMemberId[$p->club_member_id] ?? null,
                    'finishedAt' => $p->finished_at,
                ]),
            'nextMeeting' => $this->resource->nextMeeting ? new MeetingResource($this->resource->nextMeeting) : null,
            'turnOrder' => $this->resource->turnOrder?->map(function ($to, int $index) use ($activeCandidateProposerId, $currentCycle, $nextSelector) {
                $isCurrentHead = $index === 0;
                $isUpcoming = $index === 1;
                $isNextSelector = $nextSelector !== null && $to->club_member_id === $nextSelector->id;

                return [
                'memberId' => $to->club_member_id,
                'name' => $to->clubMember?->user?->name,
                'avatarUrl' => MemberAvatar::url($to->clubMember),
                'status' => match (true) {
                    $isCurrentHead && !$currentCycle => 'Текущий',
                    $isUpcoming => 'Выбирает следующую',
                    default => '',
                },
                'active' => $isCurrentHead,
                'isNextSelector' => $isNextSelector,
                'isChoosingNow' => $activeCandidateProposerId !== null && $to->club_member_id === $activeCandidateProposerId,
                'isCurrentCycleProposer' => $currentCycle !== null && $to->club_member_id === $currentCycle->proposer_id,
                'cycleNumber' => $isCurrentHead ? ($currentCycle?->cycle_number) : null,
                ];
            }),
            'activeCandidate' => $this->resource->activeCandidate
                ? new BookCandidateResource($this->resource->activeCandidate)
                : null,
            'nextAction' => $this->resource->nextAction->toArray(),
            'lifecycle' => [
                'state' => $this->resource->activeCandidate
                    ? $this->resource->activeCandidate->status->value
                    : ($currentCycle ? 'reading' : 'awaiting_next_book'),
                'currentCycleStatus' => $currentCycle?->status->value,
                'currentCycleId' => $currentCycle?->id ?? $proposedCycle?->id,
                'currentCycleNumber' => $currentCycle?->cycle_number ?? $proposedCycle?->cycle_number,
                'nextSelector' => $nextSelector ? new MemberResource($nextSelector) : null,
                'nextSelectorName' => $nextSelector?->user?->name,
                'nextSelectorQueueEmpty' => $this->resource->nextSelectorQueueEmpty,
                'shouldShowChooseBookBanner' => $isNextSelector && $this->resource->nextSelectorQueueEmpty && $nextSelector?->id !== $activeCandidateProposerId,
                'canCompleteCycle' => $currentCycle !== null && $this->resource->missingRatings->isEmpty(),
                'missingRatings' => MemberResource::collection($this->resource->missingRatings),
            ],
            'clubStats' => [
                ['value' => (string) $this->resource->completedCyclesCount, 'label' => 'Прочитано книг'],
                ['value' => (string) round($this->resource->averageRating, 1), 'label' => 'Средний рейтинг'],
                ['value' => (string) $this->resource->activeMembersCount, 'label' => 'Участников'],
                ['value' => '12', 'label' => 'Встреч в год'],
            ],
        ];
    }

    private function formatProgressLabel(?\App\Models\ReadingProgress $progress): string
    {
        if (! $progress) {
            return '0%';
        }

        $parts = [];

        if ($progress->progress_percent !== null) {
            $parts[] = "{$progress->progress_percent}%";
        }

        if ($progress->current_page !== null) {
            $parts[] = "стр. {$progress->current_page}";
        }

        if ($progress->notes) {
            $parts[] = $progress->notes;
        }

        if ($parts !== []) {
            return implode(' · ', $parts);
        }

        return match ($progress->status->value) {
            'not_started' => 'Не начал',
            'reading' => 'Читает',
            'finished' => 'Закончил',
            'abandoned' => 'Бросил',
            default => $progress->status->value,
        };
    }

    private function canEditBook(Request $request, ?\App\Models\ReadingCycle $cycle): bool
    {
        $user = $request->user();
        if (! $user || ! $cycle) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'developer'])) {
            return true;
        }

        return $cycle->status !== ReadingCycleStatusEnum::Completed
            && $user->clubMember?->id === $cycle->proposer_id;
    }

    /**
     * @return array<int, string>
     */
    private function owlMedalsByMemberId(?ReadingCycle $cycle, ?Meeting $meeting): array
    {
        if (! $cycle || ! $meeting) {
            return [];
        }

        $attendingMemberIds = $meeting->rsvps
            ->filter(fn ($rsvp) => $rsvp->status === MeetingRsvpStatusEnum::Attending)
            ->pluck('club_member_id')
            ->flip();

        if ($attendingMemberIds->isEmpty()) {
            return [];
        }

        $medals = ['gold', 'silver', 'bronze'];

        return $cycle->readingProgress
            ->filter(fn (ReadingProgress $progress) => $progress->status === ReadingProgressStatusEnum::Finished
                && $progress->finished_at !== null
                && $attendingMemberIds->has($progress->club_member_id))
            ->sortBy(fn (ReadingProgress $progress) => $progress->finished_at)
            ->take(3)
            ->values()
            ->mapWithKeys(fn (ReadingProgress $progress, int $index) => [
                $progress->club_member_id => $medals[$index],
            ])
            ->all();
    }

    private function sortProgress(ReadingProgress $a, ReadingProgress $b): int
    {
        $progressDiff = ($b->progress_percent ?? 0) <=> ($a->progress_percent ?? 0);
        if ($progressDiff !== 0) {
            return $progressDiff;
        }

        if ($a->finished_at && $b->finished_at) {
            $finishedAtDiff = $a->finished_at->getTimestamp() <=> $b->finished_at->getTimestamp();
            if ($finishedAtDiff !== 0) {
                return $finishedAtDiff;
            }
        } elseif ($a->finished_at) {
            return -1;
        } elseif ($b->finished_at) {
            return 1;
        }

        return $a->club_member_id <=> $b->club_member_id;
    }
}
