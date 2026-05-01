<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberBookQueueItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'status' => $this->status->value,
            'reason' => $this->reason,
            'description' => $this->description,
            'book' => new BookResource($this->whenLoaded('book')),
        ];
    }
}
