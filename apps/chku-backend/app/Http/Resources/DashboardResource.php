<?php

namespace App\Http\Resources;

use App\DTOs\DashboardData;
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

        return [
            'club' => new ClubResource($this->resource->club),
            'currentBook' => $book ? [
                'title' => $book->title,
                'coverTitle' => $book->title,
                'author' => $book->author,
                'selectedBy' => $currentCycle->proposer?->user?->name,
                'description' => $book->description,
                'progress' => $progress?->progress_percent ?? 0,
                'progressLabel' => $this->formatProgressLabel($progress),
            ] : null,
            'memberProgress' => $this->resource->memberProgress?->map(fn ($p) => [
                'initials' => $p->clubMember?->initials,
                'name' => $p->clubMember?->user?->name,
                'status' => $p->status->value === 'finished' ? 'Закончила' : null,
                'progress' => $p->progress_percent,
                'badge' => $p->status->value === 'reading' ? 'Читает' : null,
            ]),
            'nextMeeting' => $this->resource->nextMeeting ? new MeetingResource($this->resource->nextMeeting) : null,
            'turnOrder' => $this->resource->turnOrder?->map(fn ($to) => [
                'name' => $to->position . '. ' . $to->clubMember?->user?->name,
                'status' => $to->is_current ? 'Текущий' : ($to->is_next ? 'Выбирает следующую' : ''),
                'active' => $to->is_next,
            ]),
            'activeCandidate' => $this->resource->activeCandidate
                ? new BookCandidateResource($this->resource->activeCandidate)
                : null,
            'lifecycle' => [
                'state' => $this->resource->activeCandidate
                    ? $this->resource->activeCandidate->status->value
                    : ($currentCycle ? 'reading' : 'awaiting_next_book'),
                'currentCycleStatus' => $currentCycle?->status->value,
                'nextSelector' => $nextSelector ? new MemberResource($nextSelector) : null,
                'nextSelectorQueueEmpty' => $this->resource->nextSelectorQueueEmpty,
                'shouldShowChooseBookBanner' => $isNextSelector && $this->resource->nextSelectorQueueEmpty,
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

        return implode(' · ', $parts) ?: $progress->status->value;
    }
}
