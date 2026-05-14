<?php

namespace App\Http\Resources;

use App\Services\MemberCycleHistoryService;
use App\Support\MemberAvatar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $readingHistory = app(MemberCycleHistoryService::class)->forMember($this->resource);

        return [
            'id' => $this->id,
            'name' => $this->user?->name,
            'avatarUrl' => MemberAvatar::url($this->resource),
            'email' => $this->user?->email,
            'isActive' => $this->is_active,
            'memberSince' => $this->joined_at?->format('Y'),
            'favoriteGenreId' => $this->favorite_genre_id,
            'favoriteGenre' => $this->favoriteGenre?->name,
            'stats' => [
                'read' => $readingHistory->count(),
                'proposed' => $this->proposedCycles()->count(),
                'meetings' => $this->meetingRsvps()->where('status', 'attending')->count(),
            ],
            'readingHistory' => $readingHistory,
        ];
    }
}
