<?php

namespace Tests\Feature;

use App\Enums\BookCandidateResponseEnum;
use App\Enums\BookCandidateStatusEnum;
use App\Enums\MemberBookQueueItemStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Events\MemberJoinedClub;
use App\Events\MemberResumed;
use App\Models\BookCandidate;
use App\Models\BookCandidateResponse;
use App\Models\ClubMember;
use App\Models\MeetingRsvp;
use App\Models\ReadingCycle;
use App\Models\TurnOrder;
use App\Models\User;
use App\Services\BookSelectionStateMachine;
use App\Services\TurnOrderService;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MembershipPauseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestDatabaseSeeder::class);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $admin->assignRole('admin');
        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();
        $this->actingAs($admin)->startSession();
    }

    public function test_pausing_member_stores_reason_and_moves_them_out_of_turn_order(): void
    {
        $member = $this->member('mikhail@example.com');

        $this->postJson("/api/members/{$member->id}/deactivate", ['reason' => 'paused'])
            ->assertOk()
            ->assertJsonPath('message', 'Участник поставлен на паузу.');

        $member->refresh();
        $this->assertFalse($member->is_active);
        $this->assertSame('paused', $member->deactivation_reason->value);
        $this->assertNotNull($member->deactivated_at);
        $this->assertNotContains('mikhail@example.com', $this->turnOrderEmails());
    }

    public function test_pausing_selector_withdraws_their_candidate_and_returns_book_to_queue(): void
    {
        $candidate = $this->createProposedCandidate();
        $proposer = $candidate->proposer;

        $this->postJson("/api/members/{$proposer->id}/deactivate", ['reason' => 'paused'])->assertOk();

        $this->assertSame(BookCandidateStatusEnum::Withdrawn, $candidate->refresh()->status);
        $this->assertSame(MemberBookQueueItemStatusEnum::Queued, $candidate->queueItem->refresh()->status);
        $this->assertFalse(BookCandidate::query()
            ->where('proposer_id', $proposer->id)
            ->whereIn('status', ['pending', 'awaiting_owner_confirmation'])
            ->exists());
    }

    public function test_pausing_last_pending_member_completes_candidate_check(): void
    {
        $candidate = $this->createProposedCandidate();
        $pendingMember = $candidate->responses()
            ->where('response', BookCandidateResponseEnum::Pending->value)
            ->where('club_member_id', '!=', auth()->user()->clubMember->id)
            ->firstOrFail()
            ->clubMember;
        $candidate->responses()
            ->where('club_member_id', '!=', $pendingMember->id)
            ->update(['response' => BookCandidateResponseEnum::NotRead->value]);

        $this->postJson("/api/members/{$pendingMember->id}/deactivate", ['reason' => 'paused'])->assertOk();

        $this->assertSame(BookCandidateStatusEnum::AwaitingOwnerConfirmation, $candidate->refresh()->status);
    }

    public function test_pausing_member_removes_them_from_current_meeting(): void
    {
        $member = $this->member('mikhail@example.com');
        $meeting = ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail()->meeting()->firstOrFail();
        MeetingRsvp::updateOrCreate(
            ['meeting_id' => $meeting->id, 'club_member_id' => $member->id],
            ['status' => 'attending'],
        );

        $this->postJson("/api/members/{$member->id}/deactivate", ['reason' => 'paused'])->assertOk();

        $this->assertDatabaseMissing('meeting_rsvps', ['meeting_id' => $meeting->id, 'club_member_id' => $member->id]);
    }

    public function test_resuming_member_goes_to_end_of_turn_order_and_joins_candidate_check(): void
    {
        Event::fake([MemberResumed::class, MemberJoinedClub::class]);
        $member = $this->member('mikhail@example.com');
        $this->postJson("/api/members/{$member->id}/deactivate", ['reason' => 'paused'])->assertOk();

        $candidate = $this->createProposedCandidate();
        $candidate->responses()->update(['response' => BookCandidateResponseEnum::NotRead->value]);
        app(BookSelectionStateMachine::class)->recalculateCandidate($candidate);
        $this->assertSame(BookCandidateStatusEnum::AwaitingOwnerConfirmation, $candidate->refresh()->status);

        $this->postJson("/api/members/{$member->id}/activate")->assertOk();

        $this->assertSame('mikhail@example.com', collect($this->turnOrderEmails())->last());
        $this->assertNull($member->refresh()->deactivation_reason);
        $this->assertSame(BookCandidateStatusEnum::Pending, $candidate->refresh()->status);
        $this->assertDatabaseHas('book_candidate_responses', [
            'book_candidate_id' => $candidate->id,
            'club_member_id' => $member->id,
            'response' => BookCandidateResponseEnum::Pending->value,
        ]);
        Event::assertDispatched(MemberResumed::class);
        Event::assertNotDispatched(MemberJoinedClub::class);
    }

    public function test_resuming_member_during_active_cycle_gets_reading_progress(): void
    {
        $member = $this->member('mikhail@example.com');
        $this->postJson("/api/members/{$member->id}/deactivate", ['reason' => 'paused'])->assertOk();
        $member->readingProgress()->delete();

        $this->postJson("/api/members/{$member->id}/activate")->assertOk();

        $this->assertDatabaseHas('reading_progress', [
            'reading_cycle_id' => ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail()->id,
            'club_member_id' => $member->id,
            'progress_percent' => 0,
        ]);
    }

    public function test_inactive_member_cannot_take_part_in_club_actions(): void
    {
        $member = $this->member('mikhail@example.com');
        $this->postJson("/api/members/{$member->id}/deactivate", ['reason' => 'paused'])->assertOk();
        $meeting = ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail()->meeting()->firstOrFail();

        $this->actingAs($member->user);

        $this->patchJson('/api/reading-progress/me', ['progressPercent' => 50])->assertForbidden();
        $this->putJson('/api/reading-cycles/current/rating-review', ['rating' => 7])->assertForbidden();
        $this->patchJson("/api/meetings/{$meeting->id}/rsvps/me", ['status' => 'attending'])->assertForbidden();
        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.nextAction.type', 'paused');
    }

    private function createProposedCandidate(): BookCandidate
    {
        $cycle = ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail();
        $cycle->update(['status' => ReadingCycleStatusEnum::Completed, 'completed_at' => now()]);

        app(TurnOrderService::class)->rotateAfterCompletedCycle($cycle);
        app(BookSelectionStateMachine::class)->createCandidateForCurrentSelector($cycle->club_id);

        return BookCandidate::where('status', BookCandidateStatusEnum::Pending)->latest()->firstOrFail();
    }

    private function member(string $email): ClubMember
    {
        return ClubMember::whereHas('user', fn ($query) => $query->where('email', $email))->firstOrFail();
    }

    private function turnOrderEmails(): array
    {
        return app(TurnOrderService::class)
            ->orderedTurnOrders($this->member('elena@example.com')->club_id)
            ->map(fn (TurnOrder $order) => $order->clubMember?->user?->email)
            ->all();
    }
}
