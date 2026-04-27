<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\MeetingResource;
use App\Models\Meeting;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        ]);

        $changes = array_intersect_key($meeting->toArray(), $payload);
        $meeting->update($payload);

        $actor = $request->user();
        if ($actor) {
            $this->auditLog->logMeetingUpdated($meeting, $actor, $changes);
        }

        return new MeetingResource(
            $meeting->load('readingCycle.book', 'rsvps.clubMember.user')
        );
    }
}
