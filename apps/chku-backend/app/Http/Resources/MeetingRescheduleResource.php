<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingRescheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'oldDate' => $this->old_date?->format('Y-m-d'),
            'oldTime' => $this->old_time,
            'newDate' => $this->new_date?->format('Y-m-d'),
            'newTime' => $this->new_time,
            'reason' => $this->reason,
            'actorName' => $this->actor?->name,
            'createdAt' => $this->created_at,
        ];
    }
}
