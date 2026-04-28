<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user?->name,
            'initials' => $this->initials,
            'email' => $this->user?->email,
            'isActive' => $this->is_active,
            'memberSince' => $this->joined_at?->format('Y'),
            'favoriteGenreId' => $this->favorite_genre_id,
            'favoriteGenre' => $this->favoriteGenre?->name,
            'stats' => [
                'read' => $this->readingProgress()->where('status', 'finished')->count(),
                'proposed' => $this->proposedCycles()->count(),
                'meetings' => $this->meetingRsvps()->where('status', 'attending')->count(),
            ],
            'readingHistory' => $this->readingProgress()
                ->with('readingCycle.book', 'readingCycle.proposer.user')
                ->where('status', 'finished')
                ->latest()
                ->get()
                ->map(fn ($progress) => [
                    'title' => $progress->readingCycle?->book?->title,
                    'coverTitle' => $progress->readingCycle?->book?->title,
                    'author' => $progress->readingCycle?->book?->author,
                    'completedLabel' => $progress->readingCycle?->completed_at?->translatedFormat('F Y')
                        ?? "Цикл #{$progress->readingCycle?->cycle_number}",
                    'proposedBy' => $progress->readingCycle?->proposer?->user?->name,
                ]),
        ];
    }
}
