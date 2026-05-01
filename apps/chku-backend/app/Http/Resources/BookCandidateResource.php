<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookCandidateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentMemberId = $request->user()?->clubMember?->id;

        return [
            'id' => $this->id,
            'book' => new BookResource($this->whenLoaded('book')),
            'proposer' => new MemberResource($this->whenLoaded('proposer')),
            'reason' => $this->reason,
            'description' => $this->description,
            'status' => $this->status->value,
            'responses' => BookCandidateResponseResource::collection($this->whenLoaded('responses')),
            'canConfirm' => $this->status->value === 'awaiting_owner_confirmation'
                && $currentMemberId === $this->proposer_id,
            'createdAt' => $this->created_at,
        ];
    }
}
