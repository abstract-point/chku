<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MeetingRsvpStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Events\CycleCompleted;
use App\Events\MeetingFinished;
use App\Events\MeetingRescheduled;
use App\Events\MeetingScheduled;
use App\Events\MeetingStarted;
use App\Events\OwlAwardsAssigned;
use App\Http\Resources\MeetingResource;
use App\Models\ClubMember;
use App\Models\Meeting;
use App\Models\MeetingReschedule;
use App\Models\MeetingRsvp;
use App\Models\Rating;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use App\Models\Review;
use App\Services\AuditLogService;
use App\Services\BookSelectionStateMachine;
use App\Services\CurrentMemberService;
use App\Services\TurnOrderService;
use Carbon\Carbon;
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
            $meeting->load($this->meetingRelations())
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

        $meeting->load('readingCycle.book');
        event(new MeetingScheduled($meeting));

        return new MeetingResource(
            $meeting->load($this->meetingRelations())
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

            $meeting->load('readingCycle.book');
            event(new MeetingRescheduled($meeting, $oldDate, $oldTime, $newDate, $newTime));
        }

        if ($actor) {
            $this->auditLog->logMeetingUpdated($meeting, $actor, $changes);
        }

        return new MeetingResource(
            $meeting->load($this->meetingRelations())
        );
    }

    public function updateMyRsvp(
        Request $request,
        Meeting $meeting,
        CurrentMemberService $currentMember,
    ): MeetingResource {
        abort_if(
            $meeting->started_at !== null || $meeting->finished_at !== null,
            422,
            'После начала встречи состав участников меняет только администратор.',
        );

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
            $meeting->refresh()->load($this->meetingRelations())
        );
    }

    public function start(Request $request, Meeting $meeting): MeetingResource|JsonResponse
    {
        $this->authorize('update', $meeting);

        abort_if($meeting->finished_at !== null, 422, 'Завершённую встречу нельзя начать заново.');
        abort_if($meeting->readingCycle?->status !== ReadingCycleStatusEnum::Active, 422, 'Встречу можно начать только в активном цикле.');

        $attendingMemberIds = $meeting->attendingMemberIds();

        abort_if(
            $attendingMemberIds->count() < Meeting::MIN_ATTENDING_MEMBERS,
            422,
            'Встречу можно начать только если минимум 2 участника отметились «Буду».',
        );

        $finishedMemberIds = ReadingProgress::query()
            ->where('reading_cycle_id', $meeting->reading_cycle_id)
            ->whereIn('club_member_id', $attendingMemberIds)
            ->where('status', ReadingProgressStatusEnum::Finished)
            ->pluck('club_member_id');

        $missing = $attendingMemberIds->diff($finishedMemberIds);
        if ($missing->isNotEmpty()) {
            return response()->json([
                'message' => 'Встречу можно начать только после того, как все участники дочитают книгу.',
                'missingMemberIds' => $missing->values(),
            ], 422);
        }

        $meetingDateTime = Carbon::parse("{$meeting->date->format('Y-m-d')} {$meeting->time}");
        abort_if(
            now() < $meetingDateTime,
            422,
            'Встречу нельзя начать раньше назначенного времени.',
        );

        if ($meeting->started_at === null) {
            $meeting->update(['started_at' => now()]);
        }

        $meeting->load('readingCycle.book');
        event(new MeetingStarted($meeting));

        return new MeetingResource(
            $meeting->refresh()->load($this->meetingRelations())
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

        $attendingMemberIds = $meeting->attendingMemberIds();

        if ($attendingMemberIds->count() < Meeting::MIN_ATTENDING_MEMBERS) {
            return response()->json([
                'message' => 'Встречу можно завершить только если минимум 2 участника отметились «Буду».',
            ], 422);
        }

        $requiredRaterIds = $meeting->requiredRaterIds();

        $ratedMemberIds = Rating::query()
            ->where('reading_cycle_id', $meeting->reading_cycle_id)
            ->whereIn('club_member_id', $requiredRaterIds)
            ->pluck('club_member_id');

        $missing = $requiredRaterIds->diff($ratedMemberIds);
        if ($missing->isNotEmpty()) {
            return response()->json([
                'message' => 'Встречу можно завершить только после оценок всех дочитавших участников встречи.',
                'missingMemberIds' => $missing->values(),
            ], 422);
        }

        $awards = [];

        DB::transaction(function () use ($meeting, $attendingMemberIds, $requiredRaterIds, $stateMachine, $turnOrder, &$awards): void {
            foreach ($requiredRaterIds as $memberId) {
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

            $turnOrder->rotateAfterCompletedCycle($meeting->readingCycle);
            $stateMachine->createCandidateForCurrentSelector($meeting->readingCycle->club_id);

            $awards = $this->owlAward->awardForCompletedCycle($meeting->readingCycle, $attendingMemberIds->all());
        });

        $meeting->load('readingCycle.book', 'readingCycle.proposer.user');

        event(new MeetingFinished($meeting));
        event(new CycleCompleted($meeting->readingCycle));
        if ($awards !== []) {
            event(new OwlAwardsAssigned($awards, $meeting->readingCycle));
        }

        return new MeetingResource(
            $meeting->refresh()->load($this->meetingRelations())
        );
    }

    public function storeAttendee(Request $request, Meeting $meeting): MeetingResource
    {
        $this->authorize('update', $meeting);

        abort_if($meeting->finished_at !== null, 422, 'Состав завершённой встречи менять нельзя.');
        abort_if($meeting->readingCycle?->status !== ReadingCycleStatusEnum::Active, 422, 'Добавлять участников можно только во встречу активного цикла.');

        $payload = $request->validate([
            'memberId' => [
                'required',
                'integer',
                Rule::exists('club_members', 'id')
                    ->where('club_id', $meeting->readingCycle->club_id)
                    ->where('is_active', true),
            ],
            'finishedReading' => ['boolean'],
            'rating' => ['nullable', 'numeric', 'min:1', 'max:10'],
            'review' => ['nullable', 'string', 'max:10000'],
        ]);

        $memberId = (int) $payload['memberId'];
        $cycleId = $meeting->reading_cycle_id;
        $rating = $payload['rating'] ?? null;
        $review = trim((string) ($payload['review'] ?? ''));

        $progress = ReadingProgress::query()
            ->where('reading_cycle_id', $cycleId)
            ->where('club_member_id', $memberId)
            ->first();
        $hasFinished = $progress?->status === ReadingProgressStatusEnum::Finished
            || ($payload['finishedReading'] ?? false);

        if ($rating !== null || $review !== '') {
            abort_if($meeting->started_at === null, 422, 'Оценку можно внести только после начала встречи.');
            abort_if(! $hasFinished, 422, 'Оценку можно внести только за участника, который дочитал книгу.');
            abort_if(
                $rating === null && ! Rating::where(['reading_cycle_id' => $cycleId, 'club_member_id' => $memberId])->exists(),
                422,
                'Чтобы сохранить отзыв, укажите оценку.',
            );
        }

        DB::transaction(function () use ($meeting, $memberId, $cycleId, $payload, $progress, $rating, $review): void {
            MeetingRsvp::updateOrCreate(
                ['meeting_id' => $meeting->id, 'club_member_id' => $memberId],
                ['status' => MeetingRsvpStatusEnum::Attending],
            );

            if (($payload['finishedReading'] ?? false) && $progress?->status !== ReadingProgressStatusEnum::Finished) {
                ReadingProgress::updateOrCreate(
                    ['reading_cycle_id' => $cycleId, 'club_member_id' => $memberId],
                    [
                        'progress_percent' => 100,
                        'status' => ReadingProgressStatusEnum::Finished,
                        'finished_at' => $progress?->finished_at ?? now(),
                    ],
                );
            }

            if ($rating !== null) {
                Rating::updateOrCreate(
                    ['reading_cycle_id' => $cycleId, 'club_member_id' => $memberId],
                    ['rating' => $rating],
                );
            }

            if ($review !== '') {
                Review::updateOrCreate(
                    ['reading_cycle_id' => $cycleId, 'club_member_id' => $memberId],
                    ['text' => $review],
                );
            }
        });

        $member = ClubMember::with('user')->findOrFail($memberId);
        $this->auditLog->log(
            $request->user(),
            'meeting.attendee_entered',
            "meeting:{$meeting->id}",
            collect([
                "Участник {$member->user?->name} добавлен во встречу",
                ($payload['finishedReading'] ?? false) ? 'отмечен как дочитавший' : null,
                $rating !== null ? "оценка {$rating}" : null,
                $review !== '' ? 'отзыв внесён' : null,
            ])->filter()->implode(', '),
        );

        return new MeetingResource(
            $meeting->refresh()->load($this->meetingRelations())
        );
    }

    public function destroyRsvp(Request $request, Meeting $meeting, ClubMember $member): JsonResponse
    {
        $this->authorize('update', $meeting);

        abort_if($meeting->finished_at !== null, 422, 'Состав завершённой встречи менять нельзя.');

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

    private function meetingRelations(): array
    {
        return [
            'readingCycle.book',
            'readingCycle.ratings',
            'readingCycle.reviews',
            'rsvps.clubMember.user',
            'rsvps.clubMember.favoriteGenres',
            'reschedules.actor',
            'readingCycle.discussionMessages' => fn ($q) => $q->root()->with([
                'replies.clubMember.user',
                'clubMember.user',
            ]),
        ];
    }
}
