<?php

namespace App\Http\Resources;

use App\Enums\BookCandidateStatusEnum;
use App\Enums\MeetingRsvpStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Support\MemberAvatar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $book = $this->book;
        $firstGenre = $book?->genres?->first();
        $avgRating = $this->ratings->avg('rating') ?? 0;
        $meeting = $this->relationLoaded('meeting') ? $this->meeting : null;
        $rsvps = $meeting?->rsvps;
        $attendingCount = $rsvps?->where('status', MeetingRsvpStatusEnum::Attending)->count() ?? 0;
        $rsvpCount = $rsvps?->count() ?? 0;
        $candidate = $this->bookCandidate;
        $cycleIsCompleted = $this->status === ReadingCycleStatusEnum::Completed;

        return [
            'id' => $this->id,
            'cycleNumber' => $this->cycle_number,
            'cycleLabel' => "Цикл #{$this->cycle_number}",
            'status' => $this->status->value,
            'statusLabel' => $this->statusLabel(),
            'completedLabel' => $this->completed_at?->translatedFormat('F Y'),
            'canEditBook' => $this->canEditBook($request),
            'book' => new BookResource($this->whenLoaded('book')),
            'coverTitle' => $book?->title,
            'genre' => $firstGenre?->slug,
            'genreLabel' => $firstGenre?->name,
            'proposedById' => $this->proposer_id,
            'proposedBy' => $this->whenLoaded('proposer', fn () => $this->proposer->user?->name),
            'proposerAvatarUrl' => $this->whenLoaded('proposer', fn () => MemberAvatar::url($this->proposer)),
            'rating' => round($avgRating, 1),
            'averageRating' => round($avgRating, 1),
            'ratingsCount' => $this->ratings->count(),
            'reviewsCount' => $this->reviews->count(),
            'attendingCount' => $attendingCount,
            'rsvpCount' => $rsvpCount,
            'meetingLabel' => $this->whenLoaded('meeting', fn () => $meeting?->date?->format('d F Y') . ', ' . $meeting?->place),
            'meeting' => $meeting ? [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'date' => $meeting->date?->format('Y-m-d'),
                'time' => $meeting->time,
                'place' => $meeting->place,
                'link' => $meeting->link,
                'isOnline' => (bool) $meeting->is_online,
                'status' => $cycleIsCompleted || $meeting->finished_at
                    ? 'finished'
                    : ($meeting->started_at ? 'started' : 'scheduled'),
                'attendingCount' => $attendingCount,
                'rsvpCount' => $rsvpCount,
            ] : null,
            'candidate' => $candidate ? new BookCandidateResource($candidate) : null,
            'memberProgress' => ReadingProgressResource::collection($this->whenLoaded('readingProgress')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'discussion' => DiscussionMessageResource::collection(
                $this->whenLoaded('discussionMessages', fn () => $this->discussionMessages->whereNull('parent_id'))
            ),
        ];
    }

    private function statusLabel(): string
    {
        return match ($this->status) {
            ReadingCycleStatusEnum::Proposed => 'На проверке',
            ReadingCycleStatusEnum::Active => 'Читаем сейчас',
            ReadingCycleStatusEnum::Completed => 'Завершен',
        };
    }

    private function canEditBook(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'developer'])) {
            return true;
        }

        return $this->status !== ReadingCycleStatusEnum::Completed
            && $user->clubMember?->id === $this->proposer_id;
    }
}
