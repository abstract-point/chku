<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscussionMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'memberName' => $this->whenLoaded('clubMember', fn () => $this->clubMember->user?->name),
            'memberInitials' => $this->whenLoaded('clubMember', fn () => $this->clubMember->initials),
            'dateLabel' => $this->context_label,
            'text' => $this->text,
        ];
    }
}
