<?php

namespace Database\Seeders;

use App\Enums\MemberBookQueueItemStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Models\Book;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\Meeting;
use App\Models\MemberBookQueueItem;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use App\Models\TurnOrder;
use App\Services\TurnOrderService;
use Illuminate\Database\Seeder;

class CurrentTestCycleSeeder extends Seeder
{
    public function run(): void
    {
        $club = Club::first();

        // 1. Создаём стабильную очередь из активных участников
        $this->seedTurnOrder($club);

        $members = $this->getMembers();
        $books = $this->getBooks();

        // Первый в очереди (head) — выбирает текущую книгу
        $ordered = app(TurnOrderService::class)->orderedTurnOrders($club->id);
        $proposer = $ordered->first()?->clubMember ?? $members->first();

        $currentCycle = ReadingCycle::create([
            'club_id' => $club->id,
            'book_id' => $books['cvety-dlya-elzherona']->id,
            'proposer_id' => $proposer->id,
            'cycle_number' => 42,
            'status' => ReadingCycleStatusEnum::Active,
        ]);

        Meeting::create([
            'reading_cycle_id' => $currentCycle->id,
            'title' => 'Встреча клуба',
            'date' => '2024-04-20',
            'time' => '19:00:00',
            'place' => 'Библиотека',

        ]);

        // Прогресс только для активных участников
        foreach ($members as $member) {
            $email = $member->user->email;
            $progressData = match ($email) {
                'elena@example.com' => [ReadingProgressStatusEnum::Reading, 72],
                'mikhail@example.com' => [ReadingProgressStatusEnum::Reading, 94],
                'admin@example.com' => [ReadingProgressStatusEnum::NotStarted, 0],
                default => null,
            };

            if ($progressData) {
                ReadingProgress::create([
                    'reading_cycle_id' => $currentCycle->id,
                    'club_member_id' => $member->id,
                    'status' => $progressData[0],
                    'progress_percent' => $progressData[1],
                ]);
            }
        }

        // Очередь книг только для активных участников
        MemberBookQueueItem::create([
            'club_member_id' => $members->firstWhere('user.email', 'elena@example.com')?->id,
            'book_id' => $books['cvety-dlya-elzherona']->id,
            'status' => MemberBookQueueItemStatusEnum::Approved,
        ]);

        $elenaFirst = MemberBookQueueItem::create([
            'club_member_id' => $members->firstWhere('user.email', 'elena@example.com')?->id,
            'book_id' => $books['shum-vremeni']->id,
            'status' => MemberBookQueueItemStatusEnum::Queued,
        ]);

        $elenaSecond = MemberBookQueueItem::create([
            'club_member_id' => $members->firstWhere('user.email', 'elena@example.com')?->id,
            'book_id' => $books['oblachnyj-atlas']->id,
            'status' => MemberBookQueueItemStatusEnum::Queued,
        ]);
        $elenaFirst->update(['next_queue_item_id' => $elenaSecond->id]);

        $mikhailFirst = MemberBookQueueItem::create([
            'club_member_id' => $members->firstWhere('user.email', 'mikhail@example.com')?->id,
            'book_id' => $books['piknik-na-obochine']->id,
            'status' => MemberBookQueueItemStatusEnum::Queued,
        ]);

        $mikhailSecond = MemberBookQueueItem::create([
            'club_member_id' => $members->firstWhere('user.email', 'mikhail@example.com')?->id,
            'book_id' => $books['kratkaya-istoriya-vremeni']->id,
            'status' => MemberBookQueueItemStatusEnum::Queued,
        ]);
        $mikhailFirst->update(['next_queue_item_id' => $mikhailSecond->id]);

        $adminFirst = MemberBookQueueItem::create([
            'club_member_id' => $members->firstWhere('user.email', 'admin@example.com')?->id,
            'book_id' => $books['piknik-na-obochine']->id,
            'status' => MemberBookQueueItemStatusEnum::Queued,
        ]);

        $adminSecond = MemberBookQueueItem::create([
            'club_member_id' => $members->firstWhere('user.email', 'admin@example.com')?->id,
            'book_id' => $books['1984']->id,
            'status' => MemberBookQueueItemStatusEnum::Queued,
        ]);
        $adminFirst->update(['next_queue_item_id' => $adminSecond->id]);
    }

    private function seedTurnOrder(Club $club): void
    {
        $members = ClubMember::with('user')
            ->where('club_id', $club->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $previous = null;

        foreach ($members as $member) {
            $order = TurnOrder::create([
                'club_id' => $club->id,
                'club_member_id' => $member->id,
                'next_turn_order_id' => null,
            ]);

            $previous?->update(['next_turn_order_id' => $order->id]);
            $previous = $order;
        }
    }

    private function getMembers(): \Illuminate\Database\Eloquent\Collection
    {
        return ClubMember::with('user')
            ->where('is_active', true)
            ->get();
    }

    private function getBooks(): array
    {
        $books = [];
        foreach (Book::all() as $book) {
            $books[$book->slug] = $book;
        }
        return $books;
    }
}
