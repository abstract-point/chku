<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BookCandidateResponseEnum;
use App\Enums\BookCandidateStatusEnum;
use App\Enums\MemberBookQueueItemStatusEnum;
use App\Enums\MeetingRsvpStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Events\BookCandidateAwaitingConfirmation;
use App\Events\BookCandidateConfirmed;
use App\Events\BookCandidateProposed;
use App\Events\BookCandidateRejected;
use App\Events\BookCandidateReplaced;
use App\Events\CycleCompleted;
use App\Events\MeetingFinished;
use App\Events\MeetingRescheduled;
use App\Events\MeetingScheduled;
use App\Events\MeetingStarted;
use App\Events\MemberDeactivated;
use App\Events\MemberFinishedReading;
use App\Events\MemberJoinedClub;
use App\Events\ReadingProgressUpdated;
use App\Models\BookCandidate;
use App\Models\BookCandidateResponse;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\Meeting;
use App\Models\MemberBookQueueItem;
use App\Models\Rating;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use App\Models\User;
use App\Services\BookSelectionStateMachine;
use App\Services\TurnOrderService;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EventDispatchTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): self
    {
        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $this->actingAs($user)->startSession();
    }

    public function test_member_joined_event_dispatched_on_store(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $genre = \App\Models\Genre::firstOrFail();

        Event::fake([MemberJoinedClub::class]);

        $response = $this->actingAs($user)->postJson('/api/members', [
            'name' => 'Новый Участник',
            'email' => 'new@example.com',
            'password' => 'password123',
            'joined_at' => now()->format('Y-m-d'),
            'role' => 'member',
            'favorite_genre_ids' => [$genre->id],
        ]);

        $response->assertSuccessful();

        Event::assertDispatched(MemberJoinedClub::class);
    }

    public function test_member_deactivated_event_dispatched(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $member = ClubMember::whereHas('user', fn ($q) => $q->where('email', 'anna@example.com'))->firstOrFail();

        Event::fake([MemberDeactivated::class]);

        $response = $this->actingAs($user)->postJson("/api/members/{$member->id}/deactivate");

        $response->assertOk();

        Event::assertDispatched(MemberDeactivated::class);
    }

    public function test_candidate_proposed_event_dispatched(): void
    {
        Event::fake([BookCandidateProposed::class]);

        $this->seed(TestDatabaseSeeder::class);
        $cycle = ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail();
        $cycle->update([
            'status' => ReadingCycleStatusEnum::Completed,
            'completed_at' => now(),
        ]);

        $clubId = Club::firstOrFail()->id;
        app(TurnOrderService::class)->rotateAfterCompletedCycle($cycle);
        app(BookSelectionStateMachine::class)->createCandidateForCurrentSelector($clubId);

        Event::assertDispatched(BookCandidateProposed::class);
    }

    public function test_candidate_rejected_event_dispatched(): void
    {
        Event::fake([BookCandidateRejected::class]);

        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $candidate = $this->createProposedCandidate();

        BookCandidateResponse::where('book_candidate_id', $candidate->id)
            ->where('club_member_id', '!=', $candidate->proposer_id)
            ->delete();

        $this->actingAs($user)->patchJson("/api/candidates/{$candidate->id}/responses/me", [
            'response' => BookCandidateResponseEnum::Read->value,
        ]);

        Event::assertDispatched(BookCandidateRejected::class);
    }

    public function test_candidate_awaiting_confirmation_event_dispatched(): void
    {
        Event::fake([BookCandidateAwaitingConfirmation::class]);

        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $candidate = $this->createProposedCandidate();
        BookCandidateResponse::where('book_candidate_id', $candidate->id)->update([
            'response' => BookCandidateResponseEnum::NotRead->value,
        ]);

        $this->actingAs($user)->patchJson("/api/candidates/{$candidate->id}/responses/me", [
            'response' => BookCandidateResponseEnum::NotRead->value,
        ]);

        Event::assertDispatched(BookCandidateAwaitingConfirmation::class);
    }

    public function test_candidate_confirmed_event_dispatched(): void
    {
        Event::fake([BookCandidateConfirmed::class]);

        $this->seed(TestDatabaseSeeder::class);
        $candidate = $this->createProposedCandidate();
        $candidate->load('proposer.user');
        BookCandidateResponse::where('book_candidate_id', $candidate->id)->update([
            'response' => BookCandidateResponseEnum::NotRead->value,
        ]);
        $candidate->update(['status' => BookCandidateStatusEnum::AwaitingOwnerConfirmation]);

        $response = $this->actingAs($candidate->proposer->user)->postJson("/api/candidates/{$candidate->id}/confirm");

        $response->assertOk();

        Event::assertDispatched(BookCandidateConfirmed::class);
    }

    public function test_candidate_replaced_event_dispatched(): void
    {
        Event::fake([BookCandidateReplaced::class]);

        $this->seed(TestDatabaseSeeder::class);
        $candidate = $this->createProposedCandidate();
        $candidate->load('proposer.user');

        $replacement = MemberBookQueueItem::query()
            ->where('club_member_id', $candidate->proposer_id)
            ->where('status', MemberBookQueueItemStatusEnum::Queued->value)
            ->firstOrFail();

        $response = $this->actingAs($candidate->proposer->user)
            ->postJson("/api/me/book-queue/{$replacement->id}/candidate");

        $response->assertOk();

        Event::assertDispatched(BookCandidateReplaced::class);
    }

    public function test_reading_progress_updated_event_dispatched(): void
    {
        Event::fake([ReadingProgressUpdated::class]);

        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $response = $this->actingAs($user)->patchJson('/api/reading-progress/me', [
            'progressPercent' => 50,
        ]);

        $response->assertOk();

        Event::assertDispatched(ReadingProgressUpdated::class);
    }

    public function test_member_finished_reading_event_dispatched(): void
    {
        Event::fake([MemberFinishedReading::class]);

        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $response = $this->actingAs($user)->patchJson('/api/reading-progress/me', [
            'progressPercent' => 100,
        ]);

        $response->assertOk();

        Event::assertDispatched(MemberFinishedReading::class);
    }

    public function test_meeting_scheduled_event_dispatched(): void
    {
        Event::fake([MeetingScheduled::class]);

        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $cycle = ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail();

        // Delete existing meeting if any
        Meeting::where('reading_cycle_id', $cycle->id)->delete();

        $response = $this->actingAs($user)->postJson('/api/meetings', [
            'reading_cycle_id' => $cycle->id,
            'title' => 'Обсуждение',
            'date' => now()->addDays(7)->format('Y-m-d'),
            'time' => '19:00',
            'place' => 'Кафе',
        ]);

        $response->assertSuccessful();

        Event::assertDispatched(MeetingScheduled::class);
    }

    public function test_meeting_rescheduled_event_dispatched(): void
    {
        Event::fake([MeetingRescheduled::class]);

        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $cycle = ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail();

        // Delete existing and create new meeting
        Meeting::where('reading_cycle_id', $cycle->id)->delete();
        $meeting = Meeting::create([
            'reading_cycle_id' => $cycle->id,
            'title' => 'Обсуждение',
            'date' => now()->addDays(7),
            'time' => '19:00',
            'place' => 'Кафе',
        ]);

        $newDate = now()->addDays(14)->format('Y-m-d');

        $response = $this->actingAs($user)->patchJson("/api/meetings/{$meeting->id}", [
            'date' => $newDate,
            'time' => '20:00',
            'rescheduleReason' => 'Переносим',
        ]);

        $response->assertOk();

        Event::assertDispatched(MeetingRescheduled::class);
    }

    public function test_meeting_started_event_dispatched(): void
    {
        Event::fake([MeetingStarted::class]);

        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $cycle = ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail();

        Meeting::where('reading_cycle_id', $cycle->id)->delete();
        $meeting = Meeting::create([
            'reading_cycle_id' => $cycle->id,
            'title' => 'Обсуждение',
            'date' => now()->subDay(),
            'time' => '10:00',
        ]);

        $members = ClubMember::where('club_id', $cycle->club_id)
            ->where('is_active', true)
            ->take(2)
            ->get();

        foreach ($members as $member) {
            ReadingProgress::where('reading_cycle_id', $cycle->id)
                ->where('club_member_id', $member->id)
                ->update([
                    'progress_percent' => 100,
                    'status' => ReadingProgressStatusEnum::Finished,
                    'finished_at' => now()->subHour(),
                ]);

            \App\Models\MeetingRsvp::create([
                'meeting_id' => $meeting->id,
                'club_member_id' => $member->id,
                'status' => MeetingRsvpStatusEnum::Attending,
            ]);
        }

        $response = $this->actingAs($user)->postJson("/api/meetings/{$meeting->id}/start");

        $response->assertOk();

        Event::assertDispatched(MeetingStarted::class);
    }

    public function test_meeting_finished_events_dispatched(): void
    {
        Event::fake([MeetingFinished::class, CycleCompleted::class]);

        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $cycle = ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail();

        Meeting::where('reading_cycle_id', $cycle->id)->delete();
        $meeting = Meeting::create([
            'reading_cycle_id' => $cycle->id,
            'title' => 'Обсуждение',
            'date' => now()->subDay(),
            'time' => '10:00',
            'started_at' => now()->subHour(),
        ]);

        $members = ClubMember::where('club_id', $cycle->club_id)
            ->where('is_active', true)
            ->take(2)
            ->get();

        foreach ($members as $member) {
            Rating::create([
                'reading_cycle_id' => $cycle->id,
                'club_member_id' => $member->id,
                'rating' => 7,
            ]);

            \App\Models\MeetingRsvp::create([
                'meeting_id' => $meeting->id,
                'club_member_id' => $member->id,
                'status' => MeetingRsvpStatusEnum::Attending,
            ]);
        }

        $response = $this->actingAs($user)->postJson("/api/meetings/{$meeting->id}/finish");

        $response->assertOk();

        Event::assertDispatched(MeetingFinished::class);
        Event::assertDispatched(CycleCompleted::class);
    }

    private function createProposedCandidate(): BookCandidate
    {
        $cycle = ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail();
        $cycle->update([
            'status' => ReadingCycleStatusEnum::Completed,
            'completed_at' => now(),
        ]);

        $clubId = Club::firstOrFail()->id;

        app(TurnOrderService::class)->rotateAfterCompletedCycle($cycle);
        app(BookSelectionStateMachine::class)->createCandidateForCurrentSelector($clubId);

        return BookCandidate::where('status', BookCandidateStatusEnum::Pending)->latest()->firstOrFail();
    }
}
