<?php

namespace Tests\Feature;

use App\Enums\MemberBookQueueItemStatusEnum;
use App\Models\MemberBookQueueItem;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberBookQueueApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_manage_own_book_queue(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('email', 'elena@example.com')->firstOrFail());

        $create = $this->postJson('/api/me/book-queue', [
            'title' => 'Пикник на обочине',
            'author' => 'Аркадий и Борис Стругацкие',
            'description' => 'Фантастическая повесть о Зоне.',
            'reason' => 'Хорошо подойдёт для разговора о желаниях.',
        ]);

        $create->assertCreated();
        $itemId = $create->json('data.id');

        $this->assertDatabaseHas('member_book_queue_items', [
            'id' => $itemId,
            'status' => MemberBookQueueItemStatusEnum::Queued->value,
        ]);

        $this->getJson('/api/me/book-queue')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Пикник на обочине']);
    }

    public function test_member_cannot_remove_another_members_queue_item(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('email', 'mikhail@example.com')->firstOrFail());

        $item = MemberBookQueueItem::firstOrFail();

        $this->deleteJson("/api/me/book-queue/{$item->id}")
            ->assertForbidden();
    }
}
