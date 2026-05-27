<?php

namespace Tests\Feature;

use App\Enums\BookCandidateResponseEnum;
use App\Enums\BookCandidateStatusEnum;
use App\Enums\MemberBookQueueItemStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Models\BookCandidate;
use App\Models\BookCandidateResponse;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\MemberBookQueueItem;
use App\Models\ReadingCycle;
use App\Models\TurnOrder;
use App\Models\User;
use App\Services\BookSelectionStateMachine;
use App\Services\TurnOrderService;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCandidateApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsFirstMember(): self
    {
        $user = User::where('email', 'elena@example.com')->firstOrFail();
        return $this->actingAs($user);
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

    public function test_current_member_can_answer_candidate_verification(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $this->actingAsFirstMember();

        $candidate = $this->createProposedCandidate();

        $response = $this->patchJson("/api/candidates/{$candidate->id}/responses/me", [
            'response' => BookCandidateResponseEnum::NotRead->value,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('book_candidate_responses', [
            'book_candidate_id' => $candidate->id,
            'response' => BookCandidateResponseEnum::NotRead->value,
        ]);
    }

    public function test_read_response_rejects_candidate_and_queue_item(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $this->actingAsFirstMember();

        $candidate = $this->createProposedCandidate();

        $response = $this->patchJson("/api/candidates/{$candidate->id}/responses/me", [
            'response' => BookCandidateResponseEnum::Read->value,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('book_candidates', [
            'id' => $candidate->id,
            'status' => BookCandidateStatusEnum::Rejected->value,
        ]);
        $this->assertDatabaseHas('member_book_queue_items', [
            'id' => $candidate->member_book_queue_item_id,
            'status' => MemberBookQueueItemStatusEnum::Rejected->value,
        ]);
        $this->assertDatabaseHas('book_candidates', [
            'proposer_id' => $candidate->proposer_id,
            'status' => BookCandidateStatusEnum::Pending->value,
        ]);
    }

    public function test_candidate_waits_for_owner_confirmation_when_all_active_members_have_not_read_it(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $this->actingAsFirstMember();

        $candidate = $this->createProposedCandidate();
        BookCandidateResponse::where('book_candidate_id', $candidate->id)->update([
            'response' => BookCandidateResponseEnum::NotRead->value,
        ]);

        $response = $this->patchJson("/api/candidates/{$candidate->id}/responses/me", [
            'response' => BookCandidateResponseEnum::NotRead->value,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('book_candidates', [
            'id' => $candidate->id,
            'status' => BookCandidateStatusEnum::AwaitingOwnerConfirmation->value,
        ]);
    }

    public function test_owner_can_confirm_candidate_after_current_cycle_is_completed(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $candidate = $this->createProposedCandidate();
        $candidate->load('proposer.user');

        BookCandidateResponse::where('book_candidate_id', $candidate->id)->update([
            'response' => BookCandidateResponseEnum::NotRead->value,
        ]);
        $candidate->update(['status' => BookCandidateStatusEnum::AwaitingOwnerConfirmation]);

        $this->actingAs($candidate->proposer->user);
        $turnOrderBefore = $this->turnOrderEmails($candidate->proposer->club_id);

        $response = $this->postJson("/api/candidates/{$candidate->id}/confirm");

        $response->assertOk();
        $this->assertDatabaseHas('book_candidates', [
            'id' => $candidate->id,
            'status' => BookCandidateStatusEnum::Approved->value,
        ]);
        $this->assertDatabaseHas('reading_cycles', [
            'id' => $candidate->reading_cycle_id,
            'book_id' => $candidate->book_id,
            'status' => ReadingCycleStatusEnum::Active->value,
        ]);
        $this->assertSame(43, ReadingCycle::max('cycle_number'));
        $this->assertSame($turnOrderBefore, $this->turnOrderEmails($candidate->proposer->club_id));
    }

    public function test_owner_can_replace_pending_candidate_from_personal_queue(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $candidate = $this->createProposedCandidate();
        $candidate->load('proposer.user');

        $replacement = MemberBookQueueItem::query()
            ->where('club_member_id', $candidate->proposer_id)
            ->where('status', MemberBookQueueItemStatusEnum::Queued->value)
            ->firstOrFail();

        $this->actingAs($candidate->proposer->user);
        $turnOrderBefore = $this->turnOrderEmails($candidate->proposer->club_id);

        $response = $this->postJson("/api/me/book-queue/{$replacement->id}/candidate");

        $response->assertOk();

        $this->assertDatabaseHas('book_candidates', [
            'id' => $candidate->id,
            'status' => BookCandidateStatusEnum::Rejected->value,
        ]);
        $this->assertDatabaseHas('member_book_queue_items', [
            'id' => $candidate->member_book_queue_item_id,
            'status' => MemberBookQueueItemStatusEnum::Queued->value,
        ]);
        $this->assertDatabaseHas('member_book_queue_items', [
            'id' => $replacement->id,
            'status' => MemberBookQueueItemStatusEnum::InVerification->value,
        ]);

        $activeCandidate = BookCandidate::where('status', BookCandidateStatusEnum::Pending)->latest()->firstOrFail();
        $this->assertSame($replacement->id, $activeCandidate->member_book_queue_item_id);
        $this->assertSame(
            ClubMember::where('club_id', $activeCandidate->proposer->club_id)->where('is_active', true)->count(),
            BookCandidateResponse::where('book_candidate_id', $activeCandidate->id)->count(),
        );
        $this->assertSame($turnOrderBefore, $this->turnOrderEmails($candidate->proposer->club_id));
    }

    public function test_owner_cannot_replace_candidate_after_all_members_answered_not_read(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $candidate = $this->createProposedCandidate();
        $candidate->load('proposer.user');

        BookCandidateResponse::where('book_candidate_id', $candidate->id)->update([
            'response' => BookCandidateResponseEnum::NotRead->value,
        ]);
        $candidate->update(['status' => BookCandidateStatusEnum::AwaitingOwnerConfirmation]);

        $replacement = MemberBookQueueItem::query()
            ->where('club_member_id', $candidate->proposer_id)
            ->where('status', MemberBookQueueItemStatusEnum::Queued->value)
            ->firstOrFail();

        $this->actingAs($candidate->proposer->user);

        $this->postJson("/api/me/book-queue/{$replacement->id}/candidate")
            ->assertUnprocessable();
    }

    private function turnOrderEmails(int $clubId): array
    {
        return app(TurnOrderService::class)
            ->orderedTurnOrders($clubId)
            ->map(fn (TurnOrder $order) => $order->clubMember?->user?->email)
            ->all();
    }
}
