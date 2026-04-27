<?php

namespace Tests\Feature;

use App\Enums\BookCandidateResponseEnum;
use App\Enums\BookCandidateStatusEnum;
use App\Models\BookCandidate;
use App\Models\BookCandidateResponse;
use App\Models\ClubMember;
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

    public function test_candidate_can_be_created_for_current_member(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsFirstMember();

        $response = $this->postJson('/api/candidates', [
            'title' => 'Пикник на обочине',
            'author' => 'Аркадий и Борис Стругацкие',
            'description' => 'Фантастическая повесть о Зоне и людях вокруг нее.',
            'reason' => 'Подойдет для разговора о желании, цене риска и границах знания.',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('book_candidates', [
            'status' => BookCandidateStatusEnum::Pending->value,
        ]);

        $candidateId = $response->json('data.id');
        $this->assertSame(
            ClubMember::where('is_active', true)->count(),
            BookCandidateResponse::where('book_candidate_id', $candidateId)->count(),
        );
    }

    public function test_current_member_can_answer_candidate_verification(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsFirstMember();

        $candidate = BookCandidate::where('status', BookCandidateStatusEnum::Pending)->latest()->firstOrFail();

        $response = $this->patchJson("/api/candidates/{$candidate->id}/responses/me", [
            'response' => BookCandidateResponseEnum::NotSure->value,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('book_candidate_responses', [
            'book_candidate_id' => $candidate->id,
            'response' => BookCandidateResponseEnum::NotSure->value,
        ]);
    }

    public function test_candidate_cannot_be_approved_until_all_active_members_have_not_read_it(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsFirstMember();

        $candidate = BookCandidate::where('status', BookCandidateStatusEnum::Pending)->latest()->firstOrFail();

        $response = $this->postJson("/api/candidates/{$candidate->id}/approve");

        $response->assertUnprocessable();
        $this->assertDatabaseHas('book_candidates', [
            'id' => $candidate->id,
            'status' => BookCandidateStatusEnum::Pending->value,
        ]);
    }

    public function test_candidate_can_be_approved_when_all_active_members_have_not_read_it(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsFirstMember();

        $candidate = BookCandidate::where('status', BookCandidateStatusEnum::Pending)->latest()->firstOrFail();
        BookCandidateResponse::where('book_candidate_id', $candidate->id)->update([
            'response' => BookCandidateResponseEnum::NotRead->value,
        ]);

        $response = $this->postJson("/api/candidates/{$candidate->id}/approve");

        $response->assertOk();
        $this->assertDatabaseHas('book_candidates', [
            'id' => $candidate->id,
            'status' => BookCandidateStatusEnum::Approved->value,
        ]);
        $this->assertDatabaseHas('reading_cycles', [
            'book_id' => $candidate->book_id,
            'status' => 'proposed',
        ]);
        $this->assertSame(43, ReadingCycle::max('cycle_number'));
    }
}
