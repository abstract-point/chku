<?php

namespace Tests\Feature;

use App\Enums\BookCandidateResponseEnum;
use App\Enums\BookCandidateStatusEnum;
use App\Enums\MemberBookQueueItemStatusEnum;
use App\Models\Book;
use App\Models\BookCandidate;
use App\Models\BookCandidateResponse;
use App\Models\ClubMember;
use App\Models\MemberBookQueueItem;
use App\Models\ReadingCycle;
use App\Models\User;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberBookQueueApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_manage_own_book_queue(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $this->actingAs(User::where('email', 'elena@example.com')->firstOrFail());

        $create = $this->postJson('/api/me/book-queue', [
            'title' => 'Пикник на обочине',
            'author' => 'Аркадий и Борис Стругацкие',
            'description' => 'Фантастическая повесть о Зоне.',
        ]);

        $create->assertCreated();
        $itemId = $create->json('data.id');

        $this->assertDatabaseHas('member_book_queue_items', [
            'id' => $itemId,
            'status' => MemberBookQueueItemStatusEnum::Queued->value,
        ]);

        $this->getJson('/api/me/book-queue')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Пикник на обочине'])
            ->assertJsonMissingPath('data.0.position');
    }

    public function test_member_can_reorder_linked_book_queue(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $this->actingAs(User::where('email', 'elena@example.com')->firstOrFail());

        $items = $this->getJson('/api/me/book-queue')
            ->assertOk()
            ->json('data');

        $ids = array_column($items, 'id');
        $reorderedIds = array_reverse($ids);

        $this->postJson('/api/me/book-queue/reorder', [
            'itemIds' => $reorderedIds,
        ])->assertOk()
            ->assertJsonPath('data.0.id', $reorderedIds[0])
            ->assertJsonPath('data.0.nextQueueItemId', $reorderedIds[1]);
    }

    public function test_member_can_list_rejected_books(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $this->actingAs($user);

        $member = ClubMember::where('user_id', $user->id)->firstOrFail();
        $book = Book::create([
            'title' => 'Тестовая отклонённая книга',
            'author' => 'Тестовый автор',
            'slug' => 'test-rejected-book',
            'genre_id' => 1,
            'cover_color' => '#3a405a',
        ]);

        $item = MemberBookQueueItem::create([
            'club_member_id' => $member->id,
            'book_id' => $book->id,
            'status' => MemberBookQueueItemStatusEnum::Rejected,
        ]);

        $cycle = ReadingCycle::create([
            'club_id' => $member->club_id,
            'book_id' => $book->id,
            'proposer_id' => $member->id,
            'cycle_number' => 999,
            'status' => \App\Enums\ReadingCycleStatusEnum::Proposed,
        ]);

        $candidate = BookCandidate::create([
            'book_id' => $book->id,
            'proposer_id' => $member->id,
            'reading_cycle_id' => $cycle->id,
            'member_book_queue_item_id' => $item->id,
            'status' => BookCandidateStatusEnum::Rejected,
        ]);

        $otherMember = ClubMember::where('club_id', $member->club_id)
            ->where('id', '!=', $member->id)
            ->firstOrFail();

        BookCandidateResponse::create([
            'book_candidate_id' => $candidate->id,
            'club_member_id' => $otherMember->id,
            'response' => BookCandidateResponseEnum::Read,
        ]);

        $this->getJson('/api/me/book-queue/rejected')
            ->assertOk()
            ->assertJsonPath('data.0.id', $item->id)
            ->assertJsonPath('data.0.status', 'rejected')
            ->assertJsonPath('data.0.rejectionInfo.rejectedByMembers.0', $otherMember->name)
            ->assertJsonPath('data.0.book.title', $book->title);
    }

    public function test_member_sees_only_own_rejected_books(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $elena = User::where('email', 'elena@example.com')->firstOrFail();
        $mikhail = User::where('email', 'mikhail@example.com')->firstOrFail();

        $mikhailMember = ClubMember::where('user_id', $mikhail->id)->firstOrFail();
        $book = Book::create([
            'title' => 'Чужая отклонённая книга',
            'author' => 'Чужой автор',
            'slug' => 'other-rejected-book',
            'genre_id' => 1,
            'cover_color' => '#3a405a',
        ]);

        MemberBookQueueItem::create([
            'club_member_id' => $mikhailMember->id,
            'book_id' => $book->id,
            'status' => MemberBookQueueItemStatusEnum::Rejected,
        ]);

        $this->actingAs($elena);

        $this->getJson('/api/me/book-queue/rejected')
            ->assertOk()
            ->assertJsonMissingPath('data.0.book.title');
    }

    public function test_member_cannot_remove_another_members_queue_item(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $this->actingAs(User::where('email', 'mikhail@example.com')->firstOrFail());

        $item = MemberBookQueueItem::firstOrFail();

        $this->deleteJson("/api/me/book-queue/{$item->id}")
            ->assertForbidden();
    }
}
