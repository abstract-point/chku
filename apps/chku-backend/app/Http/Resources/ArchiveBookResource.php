<?php

namespace App\Http\Resources;

use App\Enums\MeetingRsvpStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Support\MemberAvatar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArchiveBookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $book = $this->book;
        $genre = $book?->genre;
        $avgRating = $this->ratings->avg('rating') ?? 0;
        $meeting = $this->relationLoaded('meeting') ? $this->meeting : null;
        $rsvps = $this->meeting?->rsvps;
        $attendingCount = $rsvps?->where('status', MeetingRsvpStatusEnum::Attending)->count() ?? 0;
        $rsvpCount = $rsvps?->count() ?? 0;
        $cycleIsCompleted = $this->status === ReadingCycleStatusEnum::Completed;

        return [
            'slug' => $book?->slug,
            'title' => $book?->title,
            'coverTitle' => $book?->title,
            'author' => $book?->author,
            'genre' => $genre?->slug,
            'genreLabel' => $genre?->name,
            'cycleNumber' => $this->cycle_number,
            'cycleLabel' => "Цикл #{$this->cycle_number}",
            'completedLabel' => $this->completed_at?->translatedFormat('F Y'),
            'proposedBy' => $this->whenLoaded('proposer', fn () => $this->proposer->user?->name),
            'proposerAvatarUrl' => $this->whenLoaded('proposer', fn () => MemberAvatar::url($this->proposer)),
            'rating' => round($avgRating, 1),
            'averageRating' => round($avgRating, 1),
            'ratingsCount' => $this->ratings->count(),
            'reviewsCount' => $this->reviews->count(),
            'attendingCount' => $attendingCount,
            'rsvpCount' => $rsvpCount,
            'synopsis' => $book?->description,
            'meetingLabel' => $this->whenLoaded('meeting', fn () => $this->meeting->date?->format('d F Y') . ', ' . $this->meeting->place),
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
            'discussionPrompt' => $this->discussion_prompt,
            'coverColor' => $book?->cover_color,
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'discussion' => DiscussionMessageResource::collection($this->whenLoaded('discussionMessages')),
        ];
    }
}
