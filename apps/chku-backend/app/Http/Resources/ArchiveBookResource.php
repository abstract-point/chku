<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArchiveBookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $book = $this->book;
        $genre = $book?->genre;
        $avgRating = $this->ratings->avg('rating') ?? 0;

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
            'proposerInitials' => $this->whenLoaded('proposer', fn () => $this->proposer->initials),
            'rating' => round($avgRating, 1),
            'synopsis' => $book?->description,
            'meetingLabel' => $this->whenLoaded('meeting', fn () => $this->meeting->date?->format('d F Y') . ', ' . $this->meeting->place),
            'discussionPrompt' => $this->discussion_prompt,
            'coverColor' => $book?->cover_color,
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'discussion' => DiscussionMessageResource::collection($this->whenLoaded('discussionMessages')),
        ];
    }
}
