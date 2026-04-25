<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'memberName' => $this->whenLoaded('clubMember', fn () => $this->clubMember->user?->name),
            'memberInitials' => $this->whenLoaded('clubMember', fn () => $this->clubMember->initials),
            'rating' => $this->whenLoaded('clubMember', fn () => $this->readingCycle?->ratings->firstWhere('club_member_id', $this->club_member_id)?->rating),
            'text' => $this->text,
        ];
    }
}
