<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookCandidateResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member' => new MemberResource($this->whenLoaded('clubMember')),
            'response' => $this->response->value,
        ];
    }
}
