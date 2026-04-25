<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReadingCycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cycleNumber' => $this->cycle_number,
            'cycleLabel' => "Цикл #{$this->cycle_number}",
            'status' => $this->status->value,
            'discussionPrompt' => $this->discussion_prompt,
            'completedAt' => $this->completed_at,
            'book' => new BookResource($this->whenLoaded('book')),
            'proposer' => new MemberResource($this->whenLoaded('proposer')),
        ];
    }
}
