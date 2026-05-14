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
use Illuminate\Database\Seeder;

class CurrentTestCycleSeeder extends Seeder
{
    public function run(): void
    {
        $club = Club::first();
        $members = $this->getMembers();
        $books = $this->getBooks();

        $currentCycle = ReadingCycle::create([
            'club_id' => $club->id,
            'book_id' => $books['cvety-dlya-elzherona']->id,
            'proposer_id' => $members['elena@example.com']->id,
            'cycle_number' => 42,
            'status' => ReadingCycleStatusEnum::Active,
            'discussion_prompt' => 'Что важнее: интеллект или способность чувствовать?',
        ]);

        Meeting::create([
            'reading_cycle_id' => $currentCycle->id,
            'title' => 'Встреча клуба',
            'date' => '2024-04-20',
            'time' => '19:00:00',
            'place' => 'Библиотека',
            'topics' => ['Обсуждение книги', 'Выводы и впечатления'],
        ]);

        $progress = [
            'elena@example.com' => [ReadingProgressStatusEnum::Reading, 72],
            'mikhail@example.com' => [ReadingProgressStatusEnum::Reading, 94],
            'anna@example.com' => [ReadingProgressStatusEnum::Reading, 88],
            'pavel@example.com' => [ReadingProgressStatusEnum::Reading, 63],
            'marina@example.com' => [ReadingProgressStatusEnum::Finished, 100],
            'admin@example.com' => [ReadingProgressStatusEnum::NotStarted, 0],
        ];

        foreach ($progress as $email => [$status, $percent]) {
            ReadingProgress::create([
                'reading_cycle_id' => $currentCycle->id,
                'club_member_id' => $members[$email]->id,
                'status' => $status,
                'progress_percent' => $percent,
            ]);
        }

        MemberBookQueueItem::create([
            'club_member_id' => $members['elena@example.com']->id,
            'book_id' => $books['cvety-dlya-elzherona']->id,
            'position' => 1,
            'reason' => 'Книга поднимает вечный вопрос о цене знаний и о том, что делает нас людьми.',
            'description' => $books['cvety-dlya-elzherona']->description,
            'status' => MemberBookQueueItemStatusEnum::Approved,
        ]);

        MemberBookQueueItem::create([
            'club_member_id' => $members['elena@example.com']->id,
            'book_id' => $books['shum-vremeni']->id,
            'position' => 2,
            'reason' => 'Роман о компромиссе и достоинстве в эпоху террора — хороший материал для дискуссии.',
            'description' => $books['shum-vremeni']->description,
            'status' => MemberBookQueueItemStatusEnum::Queued,
        ]);

        MemberBookQueueItem::create([
            'club_member_id' => $members['elena@example.com']->id,
            'book_id' => $books['oblachnyj-atlas']->id,
            'position' => 3,
            'reason' => 'Шесть переплетённых историй из разных эпох — интересно обсудить связи между ними.',
            'description' => $books['oblachnyj-atlas']->description,
            'status' => MemberBookQueueItemStatusEnum::Queued,
        ]);

        MemberBookQueueItem::create([
            'club_member_id' => $members['mikhail@example.com']->id,
            'book_id' => $books['piknik-na-obochine']->id,
            'position' => 1,
            'reason' => 'Хочется обсудить тему непостижимого и человеческую жадность перед лицом неизвестного.',
            'description' => $books['piknik-na-obochine']->description,
            'status' => MemberBookQueueItemStatusEnum::Queued,
        ]);

        MemberBookQueueItem::create([
            'club_member_id' => $members['mikhail@example.com']->id,
            'book_id' => $books['kratkaya-istoriya-vremeni']->id,
            'position' => 2,
            'reason' => 'Научпоп, который меняет взгляд на мир. Хороший контраст после художественной прозы.',
            'description' => $books['kratkaya-istoriya-vremeni']->description,
            'status' => MemberBookQueueItemStatusEnum::Queued,
        ]);

        MemberBookQueueItem::create([
            'club_member_id' => $members['admin@example.com']->id,
            'book_id' => $books['piknik-na-obochine']->id,
            'position' => 1,
            'reason' => 'Хочется обсудить тему непостижимого и человеческую жадность перед лицом неизвестного.',
            'description' => $books['piknik-na-obochine']->description,
            'status' => MemberBookQueueItemStatusEnum::Queued,
        ]);
    }

    private function getMembers(): array
    {
        $members = [];
        foreach (ClubMember::with('user')->get() as $member) {
            $members[$member->user->email] = $member;
        }
        return $members;
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
