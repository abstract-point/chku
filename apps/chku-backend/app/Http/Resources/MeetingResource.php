<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'cycleLabel' => $this->whenLoaded('readingCycle', fn () => "Цикл #{$this->readingCycle->cycle_number}"),
            'cycleId' => $this->whenLoaded('readingCycle', fn () => $this->readingCycle->id),
            'date' => $this->date?->format('Y-m-d'),
            'time' => $this->time,
            'place' => $this->place,
            'address' => $this->address,
            'reservation' => $this->reservation,
            'link' => $this->link,
            'isOnline' => (bool) $this->is_online,
            'topics' => $this->topics,
            'notes' => $this->notes,
            'rsvps' => MeetingRsvpResource::collection($this->whenLoaded('rsvps')),
            'reschedules' => MeetingRescheduleResource::collection($this->whenLoaded('reschedules')),
            'book' => $this->whenLoaded('readingCycle', fn () => new BookResource($this->readingCycle->book)),
        ];
    }
}
