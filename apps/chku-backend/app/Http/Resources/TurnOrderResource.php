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
            'nextTurnOrderId' => $this->next_turn_order_id,
            'member' => new MemberResource($this->whenLoaded('clubMember')),
        ];
    }
}
