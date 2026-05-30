<?php

namespace App\Http\Resources;

use App\Support\MemberAvatar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscussionMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'memberId' => $this->club_member_id,
            'memberName' => $this->whenLoaded('clubMember', fn () => $this->clubMember->user?->name),
            'memberAvatarUrl' => $this->whenLoaded('clubMember', fn () => MemberAvatar::url($this->clubMember)),
            'text' => $this->text,
            'createdAt' => $this->created_at?->toISOString(),
            'parentId' => $this->parent_id,
            'canReply' => $this->can_reply,
            'replies' => DiscussionMessageResource::collection($this->whenLoaded('replies')),
        ];
    }
}
