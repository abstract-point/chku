<?php

namespace Tests\Feature;

use App\Enums\ReadingCycleStatusEnum;
use App\Models\Book;
use App\Models\Meeting;
use App\Models\ReadingCycle;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_physical_meeting(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'elena@example.com')->firstOrFail();

        $cycle = ReadingCycle::where('status', 'active')->first();
        $cycle->meeting()->delete();

        $response = $this->actingAs($admin)->postJson('/api/meetings', [
            'reading_cycle_id' => $cycle->id,
            'title' => 'Ноябрьская встреча',
            'date' => '2023-11-15',
            'time' => '19:00',
            'is_online' => false,
            'place' => 'Главное здание библиотеки',
            'address' => 'ул. Ленина, 1',
            'topics' => ['Обсуждение'],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.title', 'Ноябрьская встреча');
        $response->assertJsonPath('data.place', 'Главное здание библиотеки');
        $response->assertJsonPath('data.isOnline', false);
    }

    public function test_admin_can_create_online_meeting(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'elena@example.com')->firstOrFail();

        $cycle = ReadingCycle::where('status', 'active')->first();
        $cycle->meeting()->delete();

        $response = $this->actingAs($admin)->postJson('/api/meetings', [
            'reading_cycle_id' => $cycle->id,
            'title' => 'Онлайн-встреча',
            'date' => '2023-11-20',
            'time' => '20:00',
            'is_online' => true,
            'link' => 'https://zoom.us/j/12345',
            'topics' => ['Обсуждение в онлайне'],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.title', 'Онлайн-встреча');
        $response->assertJsonPath('data.isOnline', true);
        $response->assertJsonPath('data.link', 'https://zoom.us/j/12345');
    }

    public function test_online_meeting_requires_link(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'elena@example.com')->firstOrFail();

        $cycle = ReadingCycle::where('status', 'active')->first();
        $cycle->meeting()->delete();

        $response = $this->actingAs($admin)->postJson('/api/meetings', [
            'reading_cycle_id' => $cycle->id,
            'title' => 'Онлайн-встреча без ссылки',
            'date' => '2023-11-20',
            'time' => '20:00',
            'is_online' => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['link']);
    }

    public function test_physical_meeting_requires_place(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'elena@example.com')->firstOrFail();

        $cycle = ReadingCycle::where('status', 'active')->first();
        $cycle->meeting()->delete();

        $response = $this->actingAs($admin)->postJson('/api/meetings', [
            'reading_cycle_id' => $cycle->id,
            'title' => 'Встреча без места',
            'date' => '2023-11-20',
            'time' => '20:00',
            'is_online' => false,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['place']);
    }

    public function test_cannot_create_second_meeting_for_same_cycle(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'elena@example.com')->firstOrFail();

        $cycle = ReadingCycle::where('status', 'active')->first();

        $this->assertTrue($cycle->meeting()->exists());

        $response = $this->actingAs($admin)->postJson('/api/meetings', [
            'reading_cycle_id' => $cycle->id,
            'title' => 'Ещё одна встреча',
            'date' => '2023-11-20',
            'time' => '20:00',
            'is_online' => false,
            'place' => 'Где-то',
        ]);

        $response->assertUnprocessable();
    }

    public function test_cannot_create_meeting_for_non_active_cycle(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'elena@example.com')->firstOrFail();

        $completedCycle = ReadingCycle::where('status', 'completed')->first();

        $response = $this->actingAs($admin)->postJson('/api/meetings', [
            'reading_cycle_id' => $completedCycle->id,
            'title' => 'Встреча для завершённого цикла',
            'date' => '2023-11-20',
            'time' => '20:00',
            'is_online' => false,
            'place' => 'Где-то',
        ]);

        $response->assertUnprocessable();
    }

    public function test_member_cannot_create_meeting(): void
    {
        $this->seed(DatabaseSeeder::class);

        $member = User::where('email', 'mikhail@example.com')->firstOrFail();

        $response = $this->actingAs($member)->postJson('/api/meetings', [
            'reading_cycle_id' => 42,
            'title' => 'Встреча от участника',
            'date' => '2023-11-20',
            'time' => '20:00',
            'is_online' => false,
            'place' => 'Где-то',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_update_meeting(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'elena@example.com')->firstOrFail();

        $cycle = ReadingCycle::where('status', 'active')->first();
        $meeting = $cycle->meeting()->first();

        $response = $this->actingAs($admin)->patchJson("/api/meetings/{$meeting->id}", [
            'title' => 'Обновлённое название',
            'is_online' => true,
            'link' => 'https://meet.google.com/abc',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.title', 'Обновлённое название');
        $response->assertJsonPath('data.isOnline', true);
        $response->assertJsonPath('data.link', 'https://meet.google.com/abc');
    }

    public function test_authenticated_user_can_rsvp(): void
    {
        $this->seed(DatabaseSeeder::class);

        $member = User::where('email', 'mikhail@example.com')->firstOrFail();

        $cycle = ReadingCycle::where('status', 'active')->first();
        $meeting = $cycle->meeting()->first();

        $response = $this->actingAs($member)->patchJson("/api/meetings/{$meeting->id}/rsvps/me", [
            'status' => 'attending',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.title', $meeting->title);
    }

    public function test_authenticated_user_can_add_topic(): void
    {
        $this->seed(DatabaseSeeder::class);

        $member = User::where('email', 'anna@example.com')->firstOrFail();

        $cycle = ReadingCycle::where('status', 'active')->first();
        $meeting = $cycle->meeting()->first();

        $response = $this->actingAs($member)->postJson("/api/meetings/{$meeting->id}/topics", [
            'topic' => 'Новая тема от Анны',
        ]);

        $response->assertOk();
    }

    public function test_meeting_show_returns_is_online_and_rsvps(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $cycle = ReadingCycle::where('status', 'active')->first();
        $meeting = $cycle->meeting()->first();

        $response = $this->actingAs($user)->getJson("/api/meetings/{$meeting->id}");

        $response->assertOk();
        $response->assertJsonPath('data.isOnline', false);
        $response->assertJsonPath('data.title', $meeting->title);
        $response->assertJsonPath('data.cycleId', $cycle->id);
    }
}
