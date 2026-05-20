<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MeetingRsvpStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Http\Resources\MeetingResource;
use App\Models\ClubMember;
use App\Models\Meeting;
use App\Models\MeetingReschedule;
use App\Models\MeetingRsvp;
use App\Models\Rating;
use App\Models\ReadingCycle;
use App\Models\Review;
use App\Services\AuditLogService;
use App\Services\BookSelectionStateMachine;
use App\Services\CurrentMemberService;
use App\Services\TurnOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class MeetingController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly \App\Services\OwlAwardService $owlAward,
    ) {
    }

    public function show(Meeting $meeting): MeetingResource
    {
        $this->authorize('view', $meeting);

        return new MeetingResource(
            $meeting->load('readingCycle.book', 'readingCycle.ratings', 'readingCycle.reviews', 'rsvps.clubMember.user', 'rsvps.clubMember.favoriteGenre')
                ->load('reschedules.actor')
        );
    }

    public function store(Request $request): MeetingResource
    {
        $this->authorize('create', Meeting::class);

        $activeCycle = ReadingCycle::where('status', 'active')->first();

        $payload = $request->validate([
            'reading_cycle_id' => [
                'required',
                'integer',
                'exists:reading_cycles,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($activeCycle) {
                    if (! $activeCycle) {
                        $fail('Нет активного цикла чтения.');
                    } elseif ((int) $value !== $activeCycle->id) {
                        $fail('Встречу можно создать только для текущего активного цикла.');
                    } elseif ($activeCycle->meeting()->exists()) {
                        $fail('У текущего цикла уже есть встреча.');
                    }
                },
            ],
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'is_online' => ['boolean'],
            'place' => ['required_unless:is_online,true', 'nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'reservation' => ['nullable', 'string', 'max:255'],
            'link' => ['required_if:is_online,true', 'nullable', 'string', 'max:255'],
            'topics' => ['nullable', 'array'],
            'topics.*' => ['string'],
            'notes' => ['nullable', 'string'],
        ]);

        $meeting = Meeting::create($payload);

        $actor = $request->user();
        if ($actor) {
            $currentMember = $actor->clubMember;
            if ($currentMember) {
                MeetingRsvp::create([
                    'meeting_id' => $meeting->id,
                    'club_member_id' => $currentMember->id,
                    'status' => MeetingRsvpStatusEnum::Attending,
                ]);
            }
            $this->auditLog->logMeetingCreated($meeting, $actor);
        }

        return new MeetingResource(
            $meeting->load('readingCycle.book', 'readingCycle.ratings', 'readingCycle.reviews', 'rsvps.clubMember.user', 'rsvps.clubMember.favoriteGenre')
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
            'is_online' => ['boolean'],
            'place' => ['sometimes', 'nullable', 'string', 'max:255'],
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
            $meeting->load('readingCycle.book', 'readingCycle.ratings', 'readingCycle.reviews', 'rsvps.clubMember.user', 'rsvps.clubMember.favoriteGenre')
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
            $meeting->refresh()->load('readingCycle.book', 'readingCycle.ratings', 'readingCycle.reviews', 'rsvps.clubMember.user', 'rsvps.clubMember.favoriteGenre', 'reschedules.actor')
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
            $meeting->refresh()->load('readingCycle.book', 'readingCycle.ratings', 'readingCycle.reviews', 'rsvps.clubMember.user', 'rsvps.clubMember.favoriteGenre', 'reschedules.actor')
        );
    }

    public function start(Request $request, Meeting $meeting): MeetingResource
    {
        $this->authorize('update', $meeting);

        abort_if($meeting->finished_at !== null, 422, 'Завершённую встречу нельзя начать заново.');
        abort_if($meeting->readingCycle?->status !== ReadingCycleStatusEnum::Active, 422, 'Встречу можно начать только в активном цикле.');

        $attendingCount = $meeting->rsvps()
            ->where('status', MeetingRsvpStatusEnum::Attending->value)
            ->count();

        abort_if(
            $attendingCount < Meeting::MIN_ATTENDING_MEMBERS,
            422,
            'Встречу можно начать только если минимум 2 участника отметились «Буду».',
        );

        if ($meeting->started_at === null) {
            $meeting->update(['started_at' => now()]);
        }

        return new MeetingResource(
            $meeting->refresh()->load('readingCycle.book', 'readingCycle.ratings', 'readingCycle.reviews', 'rsvps.clubMember.user', 'rsvps.clubMember.favoriteGenre', 'reschedules.actor')
        );
    }

    public function finish(
        Request $request,
        Meeting $meeting,
        BookSelectionStateMachine $stateMachine,
        TurnOrderService $turnOrder,
    ): MeetingResource|JsonResponse {
        $this->authorize('update', $meeting);

        $meeting->loadMissing('readingCycle.club', 'rsvps');

        abort_if($meeting->started_at === null, 422, 'Сначала начните встречу.');
        abort_if($meeting->finished_at !== null, 422, 'Встреча уже завершена.');
        abort_if($meeting->readingCycle?->status !== ReadingCycleStatusEnum::Active, 422, 'Завершить можно только встречу активного цикла.');

        $attendingMemberIds = $meeting->rsvps()
            ->where('status', MeetingRsvpStatusEnum::Attending->value)
            ->pluck('club_member_id');

        if ($attendingMemberIds->count() < Meeting::MIN_ATTENDING_MEMBERS) {
            return response()->json([
                'message' => 'Встречу можно завершить только если минимум 2 участника отметились «Буду».',
            ], 422);
        }

        $ratedMemberIds = Rating::query()
            ->where('reading_cycle_id', $meeting->reading_cycle_id)
            ->whereIn('club_member_id', $attendingMemberIds)
            ->pluck('club_member_id');

        $missing = $attendingMemberIds->diff($ratedMemberIds);
        if ($missing->isNotEmpty()) {
            return response()->json([
                'message' => 'Встречу можно завершить только после оценок всех участников встречи.',
                'missingMemberIds' => $missing->values(),
            ], 422);
        }

        DB::transaction(function () use ($meeting, $attendingMemberIds, $stateMachine, $turnOrder): void {
            foreach ($attendingMemberIds as $memberId) {
                Review::firstOrCreate(
                    [
                        'reading_cycle_id' => $meeting->reading_cycle_id,
                        'club_member_id' => $memberId,
                    ],
                    ['text' => 'Промолчал'],
                );
            }

            $meeting->update(['finished_at' => now()]);

            $meeting->readingCycle->update([
                'status' => ReadingCycleStatusEnum::Completed,
                'completed_at' => now(),
            ]);

            $turnOrder->rotateAfterCompletedCycle($meeting->readingCycle->club_id);
            $stateMachine->createCandidateForCurrentSelector($meeting->readingCycle->club_id);

            $this->owlAward->awardForCompletedCycle($meeting->readingCycle, $attendingMemberIds->all());
        });

        return new MeetingResource(
            $meeting->refresh()->load('readingCycle.book', 'readingCycle.ratings', 'readingCycle.reviews', 'rsvps.clubMember.user', 'rsvps.clubMember.favoriteGenre', 'reschedules.actor')
        );
    }

    public function destroyRsvp(Request $request, Meeting $meeting, ClubMember $member): JsonResponse
    {
        $this->authorize('update', $meeting);

        $rsvp = MeetingRsvp::where('meeting_id', $meeting->id)
            ->where('club_member_id', $member->id)
            ->first();

        abort_if(! $rsvp, 404, 'Участник не найден среди RSVP встречи.');

        abort_if(
            $member->user?->hasRole('admin'),
            403,
            'Нельзя удалить администратора из участников встречи.',
        );

        $currentMember = app(CurrentMemberService::class)->get();
        if ($rsvp->club_member_id === $currentMember->id) {
            $adminAttendeesCount = $meeting->rsvps()
                ->whereHas('clubMember.user', function ($q): void {
                    $q->whereHas('roles', function ($r): void {
                        $r->where('name', 'admin');
                    });
                })
                ->count();

            if ($adminAttendeesCount <= 1) {
                abort(403, 'Нельзя удалить себя как единственного администратора встречи.');
            }
        }

        $rsvp->delete();

        return response()->json(['message' => 'Участник удалён из встречи.']);
    }
}
