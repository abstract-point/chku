<?php

namespace Tests\Feature;

use App\Enums\ReadingCycleStatusEnum;
use App\Models\ClubMember;
use App\Models\Rating;
use App\Models\ReadingCycle;
use App\Models\User;
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
    }
}
