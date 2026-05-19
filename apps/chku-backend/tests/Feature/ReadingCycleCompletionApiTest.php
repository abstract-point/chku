<?php

namespace Tests\Feature;

use App\Enums\ReadingCycleStatusEnum;
use App\Models\ClubMember;
use App\Models\Rating;
use App\Models\ReadingCycle;
use App\Models\TurnOrder;
use App\Models\User;
use App\Services\TurnOrderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingCycleCompletionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_save_rating_and_review_for_current_cycle(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('email', 'elena@example.com')->firstOrFail());

        $response = $this->putJson('/api/reading-cycles/current/rating-review', [
            'rating' => 9,
            'review' => 'Хорошая книга для обсуждения.',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('ratings', ['rating' => 9]);
        $this->assertDatabaseHas('reviews', ['text' => 'Хорошая книга для обсуждения.']);
    }

    public function test_admin_cannot_complete_cycle_until_all_active_members_rated(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'elena@example.com')->firstOrFail();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $this->postJson('/api/reading-cycles/current/complete')
            ->assertUnprocessable();
    }

    public function test_admin_can_complete_cycle_when_all_active_members_rated(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'elena@example.com')->firstOrFail();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $cycle = ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail();
        ClubMember::where('club_id', $cycle->club_id)
            ->where('is_active', true)
            ->get()
            ->each(fn (ClubMember $member) => Rating::updateOrCreate(
                ['reading_cycle_id' => $cycle->id, 'club_member_id' => $member->id],
                ['rating' => 8],
            ));

        $this->postJson('/api/reading-cycles/current/complete')
            ->assertOk();

        $this->assertDatabaseHas('reading_cycles', [
            'id' => $cycle->id,
            'status' => ReadingCycleStatusEnum::Completed->value,
        ]);
        $turnOrderService = app(TurnOrderService::class);
        $expectedProposerId = $turnOrderService->currentSelector($cycle->club_id)->id;
        $this->assertDatabaseHas('reading_cycles', [
            'cycle_number' => 43,
            'proposer_id' => $expectedProposerId,
            'status' => ReadingCycleStatusEnum::Proposed->value,
        ]);
        $expectedTurnOrder = $turnOrderService->orderedTurnOrders($cycle->club_id)
            ->map(fn (TurnOrder $order) => $order->clubMember?->user?->email)
            ->all();
        $this->assertSame($expectedTurnOrder, $this->turnOrderEmails($cycle->club_id));
    }

    private function turnOrderEmails(int $clubId): array
    {
        return app(TurnOrderService::class)
            ->orderedTurnOrders($clubId)
            ->map(fn (TurnOrder $order) => $order->clubMember?->user?->email)
            ->all();
    }
}
