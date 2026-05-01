<?php

namespace Tests\Feature;

use App\Enums\BookCandidateResponseEnum;
use App\Enums\BookCandidateStatusEnum;
use App\Enums\MemberBookQueueItemStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Models\BookCandidate;
use App\Models\BookCandidateResponse;
use App\Models\ClubMember;
use App\Models\MemberBookQueueItem;
use App\Models\ReadingCycle;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
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

    public function test_current_member_can_answer_candidate_verification(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsFirstMember();

        $candidate = BookCandidate::where('status', BookCandidateStatusEnum::Pending)->latest()->firstOrFail();

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
        $this->seed(DatabaseSeeder::class);
        $this->actingAsFirstMember();

        $candidate = BookCandidate::where('status', BookCandidateStatusEnum::Pending)->latest()->firstOrFail();

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
    }

    public function test_candidate_waits_for_owner_confirmation_when_all_active_members_have_not_read_it(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsFirstMember();

        $candidate = BookCandidate::where('status', BookCandidateStatusEnum::Pending)->latest()->firstOrFail();
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
        $this->seed(DatabaseSeeder::class);
        $this->actingAsFirstMember();

        ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->update([
            'status' => ReadingCycleStatusEnum::Completed,
            'completed_at' => now(),
        ]);

        $candidate = BookCandidate::where('status', BookCandidateStatusEnum::Pending)->latest()->firstOrFail();
        BookCandidateResponse::where('book_candidate_id', $candidate->id)->update([
            'response' => BookCandidateResponseEnum::NotRead->value,
        ]);
        $candidate->update(['status' => BookCandidateStatusEnum::AwaitingOwnerConfirmation]);

        $response = $this->postJson("/api/candidates/{$candidate->id}/confirm");

        $response->assertOk();
        $this->assertDatabaseHas('book_candidates', [
            'id' => $candidate->id,
            'status' => BookCandidateStatusEnum::Approved->value,
        ]);
        $this->assertDatabaseHas('reading_cycles', [
            'book_id' => $candidate->book_id,
            'status' => ReadingCycleStatusEnum::Active->value,
        ]);
        $this->assertSame(43, ReadingCycle::max('cycle_number'));
    }
}
