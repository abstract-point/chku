<?php

namespace App\Http\Resources;

use App\Support\MemberAvatar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user?->name,
            'avatarUrl' => MemberAvatar::url($this->resource),
            'email' => $this->user?->email,
            'isActive' => $this->is_active,
            'memberSince' => $this->joined_at?->format('Y'),
            'favoriteGenre' => $this->favoriteGenre?->name,
            'stats' => [
                'read' => $this->readingProgress()->where('status', 'finished')->count(),
                'proposed' => $this->proposedCycles()->count(),
                'meetings' => $this->meetingRsvps()->where('status', 'attending')->count(),
                'goldOwls' => $this->gold_owls_count,
                'silverOwls' => $this->silver_owls_count,
                'bronzeOwls' => $this->bronze_owls_count,
            ],
        ];
    }
}
