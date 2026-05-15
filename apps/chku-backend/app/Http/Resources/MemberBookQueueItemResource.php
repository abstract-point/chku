<?php

namespace App\Http\Resources;

use App\Enums\MemberBookQueueItemStatusEnum;
use App\Models\MemberBookQueueItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberBookQueueItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nextQueueItemId' => $this->next_queue_item_id,
            'isHead' => ! MemberBookQueueItem::query()
                ->where('club_member_id', $this->club_member_id)
                ->whereIn('status', [
                    MemberBookQueueItemStatusEnum::Queued->value,
                    MemberBookQueueItemStatusEnum::InVerification->value,
                ])
                ->where('next_queue_item_id', $this->id)
                ->exists(),
            'isCurrentCandidate' => $this->status === MemberBookQueueItemStatusEnum::InVerification,
            'canBecomeCandidate' => $this->status === MemberBookQueueItemStatusEnum::Queued,
            'status' => $this->status->value,
            'reason' => $this->reason,
            'description' => $this->description,
            'book' => new BookResource($this->whenLoaded('book')),
        ];
    }
}
