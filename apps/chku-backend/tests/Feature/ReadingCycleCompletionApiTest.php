<?php

namespace Tests\Feature;

use App\Enums\ReadingCycleStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Models\ClubMember;
use App\Models\ReadingCycle;
use App\Models\TurnOrder;
use App\Models\User;
use App\Services\TurnOrderService;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingCycleCompletionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_save_rating_and_review_for_current_cycle(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $this->actingAs($this->finishedReader('elena@example.com'));

        $response = $this->putJson('/api/reading-cycles/current/rating-review', [
            'rating' => 9,
            'review' => 'Хорошая книга для обсуждения.',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('ratings', ['rating' => 9]);
        $this->assertDatabaseHas('reviews', ['text' => 'Хорошая книга для обсуждения.']);
    }

    public function test_member_can_save_long_review_up_to_limit(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $this->actingAs($this->finishedReader('elena@example.com'));

        $review = str_repeat('Отзыв ', 1666);

        $this->putJson('/api/reading-cycles/current/rating-review', [
            'rating' => 8,
            'review' => $review,
        ])->assertOk();
        $this->assertDatabaseHas('reviews', ['text' => trim($review)]);

        $this->putJson('/api/reading-cycles/current/rating-review', [
            'rating' => 8,
            'review' => str_repeat('я', 10001),
        ])->assertUnprocessable()->assertJsonValidationErrors('review');
    }

    public function test_member_cannot_rate_before_finishing_book(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $cycle = ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail();
        $user->clubMember->readingProgress()->updateOrCreate(
            ['reading_cycle_id' => $cycle->id],
            ['progress_percent' => 60, 'status' => ReadingProgressStatusEnum::Reading, 'finished_at' => null],
        );

        $this->actingAs($user)
            ->putJson('/api/reading-cycles/current/rating-review', ['rating' => 7])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('ratings', ['reading_cycle_id' => $cycle->id, 'club_member_id' => $user->clubMember->id]);
    }

    public function test_turn_rotation_moves_completed_cycle_proposer_even_when_proposer_is_not_head(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $cycle = ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail();
        $cycle->update([
            'proposer_id' => ClubMember::whereHas('user', fn ($query) => $query->where('email', 'elena@example.com'))->firstOrFail()->id,
        ]);

        app(TurnOrderService::class)->rotateAfterCompletedCycle($cycle);

        $this->assertSame(['admin@example.com', 'mikhail@example.com', 'elena@example.com'], $this->turnOrderEmails($cycle->club_id));
    }

    public function test_turn_rotation_does_not_move_queue_when_completed_cycle_proposer_is_inactive(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $cycle = ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail();
        $cycle->update([
            'proposer_id' => ClubMember::whereHas('user', fn ($query) => $query->where('email', 'anna@example.com'))->firstOrFail()->id,
        ]);

        app(TurnOrderService::class)->rotateAfterCompletedCycle($cycle);

        $this->assertSame(['admin@example.com', 'elena@example.com', 'mikhail@example.com'], $this->turnOrderEmails($cycle->club_id));
    }

    private function finishedReader(string $email): User
    {
        $user = User::where('email', $email)->firstOrFail();
        $user->clubMember->readingProgress()->updateOrCreate(
            ['reading_cycle_id' => ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail()->id],
            ['progress_percent' => 100, 'status' => ReadingProgressStatusEnum::Finished, 'finished_at' => now()],
        );

        return $user;
    }

    private function turnOrderEmails(int $clubId): array
    {
        return app(TurnOrderService::class)
            ->orderedTurnOrders($clubId)
            ->map(fn (TurnOrder $order) => $order->clubMember?->user?->email)
            ->all();
    }
}
