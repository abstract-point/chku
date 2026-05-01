<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\MeetingResource;
use App\Models\Meeting;
use App\Models\MeetingReschedule;
use App\Models\MeetingRsvp;
use App\Services\AuditLogService;
use App\Services\CurrentMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class MeetingController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function show(Meeting $meeting): MeetingResource
    {
        $this->authorize('view', $meeting);

        return new MeetingResource(
            $meeting->load('readingCycle.book', 'rsvps.clubMember.user')
                ->load('reschedules.actor')
        );
    }

    public function store(Request $request): MeetingResource
    {
        $this->authorize('create', Meeting::class);

        $payload = $request->validate([
            'reading_cycle_id' => ['required', 'integer', 'exists:reading_cycles,id'],
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'place' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'reservation' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255'],
            'topics' => ['nullable', 'array'],
            'topics.*' => ['string'],
            'notes' => ['nullable', 'string'],
        ]);

        $meeting = Meeting::create($payload);

        $actor = $request->user();
        if ($actor) {
            $this->auditLog->logMeetingCreated($meeting, $actor);
        }

        return new MeetingResource(
            $meeting->load('readingCycle.book', 'rsvps.clubMember.user')
                ->load('reschedules.actor')
        );
    }

    public function update(Request $request, Meeting $meeting): MeetingResource
    {
        $this->authorize('update', $meeting);

        $payload = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'date' => ['sometimes', 'date'],
            'time' => ['sometimes', 'date_format:H:i'],
            'place' => ['sometimes', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'reservation' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255'],
            'topics' => ['nullable', 'array'],
            'topics.*' => ['string'],
            'notes' => ['nullable', 'string'],
            'rescheduleReason' => ['nullable', 'string', 'max:2000'],
        ]);

        $changes = array_intersect_key($meeting->toArray(), $payload);
        $oldDate = $meeting->date?->format('Y-m-d');
        $oldTime = $meeting->time;
        $newDate = $payload['date'] ?? $oldDate;
        $newTime = $payload['time'] ?? $oldTime;

        unset($payload['rescheduleReason']);
        $meeting->update($payload);

        $actor = $request->user();
        if (($oldDate !== $newDate || $oldTime !== $newTime)) {
            MeetingReschedule::create([
                'meeting_id' => $meeting->id,
                'actor_id' => $actor?->id,
                'old_date' => $oldDate,
                'old_time' => $oldTime,
                'new_date' => $newDate,
                'new_time' => $newTime,
                'reason' => $request->input('rescheduleReason'),
            ]);
        }

        if ($actor) {
            $this->auditLog->logMeetingUpdated($meeting, $actor, $changes);
        }

        return new MeetingResource(
            $meeting->load('readingCycle.book', 'rsvps.clubMember.user')
                ->load('reschedules.actor')
        );
    }

    public function updateMyRsvp(
        Request $request,
        Meeting $meeting,
        CurrentMemberService $currentMember,
    ): MeetingResource {
        $payload = $request->validate([
            'status' => ['required', Rule::in(['attending', 'not_attending', 'pending'])],
        ]);

        MeetingRsvp::updateOrCreate(
            [
                'meeting_id' => $meeting->id,
                'club_member_id' => $currentMember->get()->id,
            ],
            ['status' => $payload['status']],
        );

        return new MeetingResource(
            $meeting->refresh()->load('readingCycle.book', 'rsvps.clubMember.user', 'reschedules.actor')
        );
    }

    public function storeTopic(Request $request, Meeting $meeting): MeetingResource
    {
        $payload = $request->validate([
            'topic' => ['required', 'string', 'max:500'],
        ]);

        $topics = $meeting->topics ?? [];
        $topics[] = $payload['topic'];
        $meeting->update(['topics' => $topics]);

        return new MeetingResource(
            $meeting->refresh()->load('readingCycle.book', 'rsvps.clubMember.user', 'reschedules.actor')
        );
    }
}
