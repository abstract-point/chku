<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReadingProgressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member' => new MemberResource($this->whenLoaded('clubMember')),
            'status' => $this->status->value,
            'progressPercent' => $this->progress_percent,
            'currentPage' => $this->current_page,
            'notes' => $this->notes,
        ];
    }
}
