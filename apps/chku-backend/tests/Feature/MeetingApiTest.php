<?php

namespace Tests\Feature;

use App\Enums\ReadingCycleStatusEnum;
use App\Models\Book;
use App\Models\ClubMember;
use App\Models\Meeting;
use App\Models\MeetingRsvp;
use App\Models\Rating;
use App\Models\ReadingCycle;
use App\Models\Review;
use App\Models\TurnOrder;
use App\Models\User;
use App\Services\TurnOrderService;
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

    public function test_meeting_show_returns_ratings_and_reviews(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $cycle = ReadingCycle::where('status', 'active')->first();
        $meeting = $cycle->meeting()->first();

        $member = ClubMember::whereHas('user', fn ($query) => $query->where('email', 'mikhail@example.com'))->firstOrFail();

        Rating::create([
            'reading_cycle_id' => $cycle->id,
            'club_member_id' => $member->id,
            'rating' => 9,
        ]);

        Review::create([
            'reading_cycle_id' => $cycle->id,
            'club_member_id' => $member->id,
            'text' => 'Отличная книга.',
        ]);

        $response = $this->actingAs($user)->getJson("/api/meetings/{$meeting->id}");

        $response->assertOk();
        $response->assertJsonPath('data.ratings.0.memberId', $member->id);
        $response->assertJsonPath('data.ratings.0.value', 9);
        $response->assertJsonPath('data.reviews.0.memberId', $member->id);
        $response->assertJsonPath('data.reviews.0.text', 'Отличная книга.');
    }

    public function test_admin_can_start_meeting_for_active_cycle(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'elena@example.com')->firstOrFail();
        $cycle = ReadingCycle::where('status', 'active')->first();
        $meeting = $cycle->meeting()->first();
        $attendees = ClubMember::whereHas('user', fn ($query) => $query->whereIn('email', [
            'elena@example.com',
            'mikhail@example.com',
        ]))->get();

        $meeting->rsvps()->delete();
        foreach ($attendees as $member) {
            MeetingRsvp::create([
                'meeting_id' => $meeting->id,
                'club_member_id' => $member->id,
                'status' => 'attending',
            ]);
        }

        $response = $this->actingAs($admin)->postJson("/api/meetings/{$meeting->id}/start");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'started');
        $this->assertNotNull($meeting->refresh()->started_at);
    }

    public function test_admin_cannot_start_meeting_without_two_attending_members(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'elena@example.com')->firstOrFail();
        $cycle = ReadingCycle::where('status', 'active')->first();
        $meeting = $cycle->meeting()->first();
        $member = ClubMember::whereHas('user', fn ($query) => $query->where('email', 'elena@example.com'))->firstOrFail();

        $meeting->rsvps()->delete();

        $this->actingAs($admin)
            ->postJson("/api/meetings/{$meeting->id}/start")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Встречу можно начать только если минимум 2 участника отметились «Буду».');

        MeetingRsvp::create([
            'meeting_id' => $meeting->id,
            'club_member_id' => $member->id,
            'status' => 'attending',
        ]);

        $this->actingAs($admin)
            ->postJson("/api/meetings/{$meeting->id}/start")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Встречу можно начать только если минимум 2 участника отметились «Буду».');

        $this->assertNull($meeting->refresh()->started_at);
    }

    public function test_admin_cannot_finish_meeting_without_two_attending_members(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'elena@example.com')->firstOrFail();
        $cycle = ReadingCycle::where('status', 'active')->first();
        $meeting = $cycle->meeting()->first();
        $meeting->update(['started_at' => now()]);
        $member = ClubMember::whereHas('user', fn ($query) => $query->where('email', 'elena@example.com'))->firstOrFail();

        $meeting->rsvps()->delete();

        $this->actingAs($admin)
            ->postJson("/api/meetings/{$meeting->id}/finish")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Встречу можно завершить только если минимум 2 участника отметились «Буду».');

        MeetingRsvp::create([
            'meeting_id' => $meeting->id,
            'club_member_id' => $member->id,
            'status' => 'attending',
        ]);
        Rating::create([
            'reading_cycle_id' => $cycle->id,
            'club_member_id' => $member->id,
            'rating' => 9,
        ]);

        $this->actingAs($admin)
            ->postJson("/api/meetings/{$meeting->id}/finish")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Встречу можно завершить только если минимум 2 участника отметились «Буду».');

        $this->assertNull($meeting->refresh()->finished_at);
    }

    public function test_admin_cannot_finish_meeting_until_attending_members_rated(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'elena@example.com')->firstOrFail();
        $cycle = ReadingCycle::where('status', 'active')->first();
        $meeting = $cycle->meeting()->first();
        $meeting->update(['started_at' => now()]);

        $attendees = ClubMember::whereHas('user', fn ($query) => $query->whereIn('email', [
            'elena@example.com',
            'mikhail@example.com',
        ]))->get();

        foreach ($attendees as $member) {
            MeetingRsvp::updateOrCreate(
                ['meeting_id' => $meeting->id, 'club_member_id' => $member->id],
                ['status' => 'attending'],
            );
        }

        Rating::create([
            'reading_cycle_id' => $cycle->id,
            'club_member_id' => $attendees->first()->id,
            'rating' => 9,
        ]);

        $this->actingAs($admin)
            ->postJson("/api/meetings/{$meeting->id}/finish")
            ->assertUnprocessable()
            ->assertJsonPath('missingMemberIds.0', $attendees->last()->id);
    }

    public function test_admin_can_finish_meeting_complete_cycle_and_start_next_proposed_cycle(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'elena@example.com')->firstOrFail();
        $cycle = ReadingCycle::where('status', 'active')->first();
        $meeting = $cycle->meeting()->first();
        $meeting->update(['started_at' => now()]);

        $attendees = ClubMember::whereHas('user', fn ($query) => $query->whereIn('email', [
            'elena@example.com',
            'mikhail@example.com',
        ]))->get();

        foreach ($attendees as $index => $member) {
            MeetingRsvp::updateOrCreate(
                ['meeting_id' => $meeting->id, 'club_member_id' => $member->id],
                ['status' => 'attending'],
            );
            Rating::create([
                'reading_cycle_id' => $cycle->id,
                'club_member_id' => $member->id,
                'rating' => 8,
            ]);

            $progress = $member->readingProgress()->where('reading_cycle_id', $cycle->id)->first();
            if ($progress) {
                $progress->update([
                    'progress_percent' => 100,
                    'status' => \App\Enums\ReadingProgressStatusEnum::Finished,
                    'finished_at' => now()->subMinutes(2 - $index),
                ]);
            }
        }

        Review::create([
            'reading_cycle_id' => $cycle->id,
            'club_member_id' => $attendees->first()->id,
            'text' => 'Живое обсуждение.',
        ]);

        $response = $this->actingAs($admin)->postJson("/api/meetings/{$meeting->id}/finish");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'finished');
        $this->assertDatabaseHas('reading_cycles', [
            'id' => $cycle->id,
            'status' => ReadingCycleStatusEnum::Completed->value,
        ]);
        $this->assertDatabaseHas('reviews', [
            'reading_cycle_id' => $cycle->id,
            'club_member_id' => $attendees->last()->id,
            'text' => 'Промолчал',
        ]);
        $this->assertDatabaseHas('reading_cycles', [
            'cycle_number' => 43,
            'status' => ReadingCycleStatusEnum::Proposed->value,
        ]);
        $turnOrderService = app(TurnOrderService::class);
        $expectedProposerId = $turnOrderService->currentSelector($cycle->club_id)->id;
        $this->assertDatabaseHas('book_candidates', [
            'proposer_id' => $expectedProposerId,
        ]);
        $expectedTurnOrder = $turnOrderService->orderedTurnOrders($cycle->club_id)
            ->map(fn (TurnOrder $order) => $order->clubMember?->user?->email)
            ->all();
        $this->assertSame($expectedTurnOrder, $this->turnOrderEmails($cycle->club_id));

        // Verify owls were awarded based on finished_at order
        $first = $attendees->first();
        $last = $attendees->last();
        $this->assertEquals(1, $first->refresh()->gold_owls_count);
        $this->assertEquals(0, $first->silver_owls_count);
        $this->assertEquals(1, $last->refresh()->silver_owls_count);
        $this->assertEquals(0, $last->gold_owls_count);
    }

    private function turnOrderEmails(int $clubId): array
    {
        return app(TurnOrderService::class)
            ->orderedTurnOrders($clubId)
            ->map(fn (TurnOrder $order) => $order->clubMember?->user?->email)
            ->all();
    }
}
