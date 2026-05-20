<?php

namespace App\Http\Resources;

use App\Enums\MeetingRsvpStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Models\Meeting;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attendingMemberIds = $this->whenLoaded(
            'rsvps',
            fn () => $this->rsvps
                ->filter(fn ($rsvp) => $rsvp->status === MeetingRsvpStatusEnum::Attending)
                ->pluck('club_member_id'),
            collect(),
        );

        $ratedMemberIds = $attendingMemberIds->isEmpty()
            ? collect()
            : Rating::query()
                ->where('reading_cycle_id', $this->reading_cycle_id)
                ->whereIn('club_member_id', $attendingMemberIds)
                ->pluck('club_member_id');

        $missingRatingIds = $attendingMemberIds->diff($ratedMemberIds)->values();
        $hasQuorum = $attendingMemberIds->count() >= Meeting::MIN_ATTENDING_MEMBERS;
        $cycleIsActive = $this->relationLoaded('readingCycle')
            && $this->readingCycle?->status === ReadingCycleStatusEnum::Active;

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
            'startedAt' => $this->started_at,
            'finishedAt' => $this->finished_at,
            'status' => $this->finished_at
                ? 'finished'
                : ($this->started_at ? 'started' : 'scheduled'),
            'canStart' => $cycleIsActive && $this->started_at === null && $this->finished_at === null && $hasQuorum,
            'canFinish' => $cycleIsActive && $this->started_at !== null && $this->finished_at === null && $hasQuorum && $missingRatingIds->isEmpty(),
            'missingRatingMemberIds' => $missingRatingIds,
            'rsvps' => MeetingRsvpResource::collection($this->whenLoaded('rsvps')),
            'reschedules' => MeetingRescheduleResource::collection($this->whenLoaded('reschedules')),
            'book' => $this->whenLoaded('readingCycle', fn () => new BookResource($this->readingCycle->book)),
            'ratings' => $this->whenLoaded('readingCycle', fn () => $this->readingCycle->ratings->map(fn ($rating) => [
                'memberId' => $rating->club_member_id,
                'value' => $rating->rating,
            ])),
            'reviews' => $this->whenLoaded('readingCycle', fn () => $this->readingCycle->reviews->map(fn ($review) => [
                'memberId' => $review->club_member_id,
                'text' => $review->text,
            ])),
        ];
    }
}
