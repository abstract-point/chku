<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CycleHistoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_receives_personal_cycle_history(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'pavel@example.com')->firstOrFail();

        $response = $this->actingAs($user)->getJson('/api/me/reading-history');

        $response->assertOk();
        $response->assertJsonCount(9, 'data');
        $response->assertJsonPath('data.0.title', 'Тёмная башня');
        $response->assertJsonPath('data.0.cycleLabel', 'Цикл #10');
        $response->assertJsonPath('data.0.myRating', 9);
        $response->assertJsonPath('data.0.hasReview', true);
        $response->assertJsonPath('data.0.attendedMeeting', true);
        $response->assertJsonMissing(['title' => 'Мы']);
    }

    public function test_archive_returns_cycle_aggregates(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'pavel@example.com')->firstOrFail();

        $response = $this->actingAs($user)->getJson('/api/archive');

        $response->assertOk();
        $response->assertJsonPath('data.0.title', 'Тёмная башня');
        $response->assertJsonPath('data.0.cycleLabel', 'Цикл #10');
        $response->assertJsonPath('data.0.averageRating', 8.3);
        $response->assertJsonPath('data.0.ratingsCount', 3);
        $response->assertJsonPath('data.0.reviewsCount', 3);
        $response->assertJsonPath('data.0.attendingCount', 3);
        $response->assertJsonPath('data.0.rsvpCount', 3);
        $response->assertJsonPath('data.0.meeting.status', 'finished');
        $response->assertJsonPath('data.0.meeting.attendingCount', 3);
        $response->assertJsonPath('data.0.meeting.rsvpCount', 3);
    }
}
