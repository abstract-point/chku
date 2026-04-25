<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
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
            'favoriteGenre' => $this->favoriteGenre?->name,
        ];
    }
}
