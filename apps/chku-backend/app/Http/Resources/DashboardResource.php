<?php

namespace App\Http\Resources;

use App\DTOs\DashboardData;
use App\Enums\ReadingCycleStatusEnum;
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
                'genre' => $book->genre ? new GenreResource($book->genre) : null,
                'progress' => $progress?->progress_percent ?? 0,
                'progressLabel' => $this->formatProgressLabel($progress),
                'cycleNumber' => $currentCycle->cycle_number,
                'cycleStatus' => $currentCycle->status->value,
                'canEditBook' => $this->canEditBook($request, $currentCycle),
            ] : null,
            'memberProgress' => $this->resource->memberProgress
                ?->sortByDesc(fn ($p) => $p->progress_percent ?? 0)
                ->values()
                ->map(fn ($p, int $index) => [
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
                    'medal' => match ($index) {
                        0 => 'gold',
                        1 => 'silver',
                        2 => 'bronze',
                        default => null,
                    },
                    'finishedAt' => $p->finished_at,
                ]),
            'nextMeeting' => $this->resource->nextMeeting ? new MeetingResource($this->resource->nextMeeting) : null,
            'turnOrder' => $this->resource->turnOrder?->map(function ($to, int $index) use ($activeCandidateProposerId, $currentCycle) {
                $isCurrentHead = $index === 0;
                $isUpcoming = $index === 1;

                return [
                'name' => $to->clubMember?->user?->name,
                'avatarUrl' => MemberAvatar::url($to->clubMember),
                'status' => $isCurrentHead ? 'Текущий' : ($isUpcoming ? 'Выбирает следующую' : ''),
                'active' => $currentCycle ? $isUpcoming : $isCurrentHead,
                'isChoosingNow' => $activeCandidateProposerId !== null && $to->club_member_id === $activeCandidateProposerId,
                'isCurrentCycleProposer' => $isCurrentHead && $currentCycle !== null,
                'cycleNumber' => $isCurrentHead ? ($currentCycle?->cycle_number) : null,
                ];
            }),
            'activeCandidate' => $this->resource->activeCandidate
                ? new BookCandidateResource($this->resource->activeCandidate)
                : null,
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
}
