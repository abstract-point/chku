<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TurnOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'member' => new MemberResource($this->whenLoaded('clubMember')),
            'isCurrent' => $this->is_current,
            'isNext' => $this->is_next,
            'skippedAt' => $this->skipped_at,
        ];
    }
}
