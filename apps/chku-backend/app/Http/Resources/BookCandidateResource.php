<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookCandidateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'book' => new BookResource($this->whenLoaded('book')),
            'proposer' => new MemberResource($this->whenLoaded('proposer')),
            'reason' => $this->reason,
            'description' => $this->description,
            'status' => $this->status->value,
            'responses' => BookCandidateResponseResource::collection($this->whenLoaded('responses')),
            'createdAt' => $this->created_at,
        ];
    }
}
