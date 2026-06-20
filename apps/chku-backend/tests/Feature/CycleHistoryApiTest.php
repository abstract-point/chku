<?php

namespace Tests\Feature;

use App\Enums\ReadingProgressStatusEnum;
use App\Models\ReadingCycle;
use App\Models\User;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CycleHistoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_receives_personal_cycle_history(): void
    {
        $this->seed(TestDatabaseSeeder::class);
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

    public function test_cycles_return_cycle_aggregates_with_current_cycle_first(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'pavel@example.com')->firstOrFail();

        $response = $this->actingAs($user)->getJson('/api/cycles');

        $response->assertOk();
        $response->assertJsonPath('data.0.cycleLabel', 'Цикл #42');
        $response->assertJsonPath('data.0.status', 'active');
        $response->assertJsonPath('data.1.book.title', 'Тёмная башня');
        $response->assertJsonPath('data.1.cycleLabel', 'Цикл #10');
        $response->assertJsonPath('data.1.averageRating', 8.3);
        $response->assertJsonPath('data.1.ratingsCount', 3);
        $response->assertJsonPath('data.1.reviewsCount', 3);
        $response->assertJsonPath('data.1.attendingCount', 3);
        $response->assertJsonPath('data.1.rsvpCount', 3);
        $response->assertJsonPath('data.1.meeting.status', 'finished');
        $response->assertJsonPath('data.1.meeting.attendingCount', 3);
        $response->assertJsonPath('data.1.meeting.rsvpCount', 3);
    }

    public function test_cycle_detail_is_addressed_by_cycle_number(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'pavel@example.com')->firstOrFail();

        $response = $this->actingAs($user)->getJson('/api/cycles/10');

        $response->assertOk();
        $response->assertJsonPath('data.book.title', 'Тёмная башня');
        $response->assertJsonPath('data.cycleLabel', 'Цикл #10');
        $response->assertJsonPath('data.status', 'completed');
    }

    public function test_cycle_detail_member_progress_breaks_finished_at_ties_by_member_id(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $cycle = ReadingCycle::where('cycle_number', 42)->firstOrFail();
        $progressRows = $cycle->readingProgress()
            ->orderByDesc('club_member_id')
            ->take(2)
            ->get();
        $finishedAt = now()->milliseconds(0);

        foreach ($progressRows as $progressRow) {
            $progressRow->update([
                'status' => ReadingProgressStatusEnum::Finished,
                'progress_percent' => 100,
                'finished_at' => $finishedAt,
            ]);
        }

        $user = User::where('email', 'pavel@example.com')->firstOrFail();

        $response = $this->actingAs($user)->getJson('/api/cycles/42');

        $response->assertOk();

        $progress = collect($response->json('data.memberProgress'));
        $expectedIds = $progressRows->sortBy('club_member_id')->pluck('club_member_id')->values();

        $this->assertSame($expectedIds[0], $progress[0]['member']['id']);
        $this->assertSame($expectedIds[1], $progress[1]['member']['id']);
    }

    public function test_archive_routes_are_removed(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'pavel@example.com')->firstOrFail();

        $this->actingAs($user)->getJson('/api/archive')->assertNotFound();
        $this->actingAs($user)->getJson('/api/archive/temnaya-bashnya')->assertNotFound();
    }

    public function test_proposer_can_edit_active_cycle_book_before_archive(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $cycle = ReadingCycle::with('proposer.user')->where('cycle_number', 42)->firstOrFail();
        $user = $cycle->proposer->user;

        $response = $this->actingAs($user)->patchJson('/api/cycles/42/book', [
            'title' => 'Цветы для Элджернона. Исправленное название',
            'author' => 'Дэниел Киз',
            'description' => 'Уточнённое описание книги.',
            'genreId' => 2,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.book.title', 'Цветы для Элджернона. Исправленное название');
        $this->assertDatabaseHas('books', [
            'id' => $cycle->book_id,
            'title' => 'Цветы для Элджернона. Исправленное название',
        ]);
    }

    public function test_proposer_cannot_edit_completed_cycle_book(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'marina@example.com')->firstOrFail();

        $this->actingAs($user)->patchJson('/api/cycles/10/book', [
            'title' => 'Новая башня',
            'author' => 'Стивен Кинг',
        ])->assertForbidden();
    }

    public function test_admin_can_edit_completed_cycle_book_cover(): void
    {
        Storage::fake('public');

        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'admin@example.com')->firstOrFail();
        $cycle = ReadingCycle::where('cycle_number', 10)->firstOrFail();

        $file = UploadedFile::fake()->image('cover.jpg');
        $response = $this->actingAs($user)->patch('/api/cycles/10/book', [
            'title' => 'Тёмная башня',
            'author' => 'Стивен Кинг',
            'description' => 'Обновленная карточка книги.',
            'coverFile' => $file,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('book_covers', [
            'book_id' => $cycle->book_id,
        ]);
    }
}
