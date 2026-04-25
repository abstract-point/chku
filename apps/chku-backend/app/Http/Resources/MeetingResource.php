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
            'date' => $this->date?->format('Y-m-d'),
            'time' => $this->time,
            'place' => $this->place,
            'address' => $this->address,
            'reservation' => $this->reservation,
            'link' => $this->link,
            'topics' => $this->topics,
            'notes' => $this->notes,
            'rsvps' => MeetingRsvpResource::collection($this->whenLoaded('rsvps')),
        ];
    }
}
