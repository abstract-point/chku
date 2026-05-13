<?php

namespace Database\Seeders;

use App\Enums\BookCandidateResponseEnum;
use App\Enums\BookCandidateStatusEnum;
use App\Enums\MemberBookQueueItemStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Models\Book;
use App\Models\BookCandidate;
use App\Models\BookCandidateResponse;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\MemberBookQueueItem;
use App\Models\ReadingCycle;
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
            'proposer_id' => $members['П1']->id,
            'cycle_number' => 11,
            'status' => ReadingCycleStatusEnum::Proposed,
            'discussion_prompt' => 'Что важнее: интеллект или способность чувствовать?',
        ]);

        $queueItem = MemberBookQueueItem::create([
            'club_member_id' => $members['П1']->id,
            'book_id' => $books['cvety-dlya-elzherona']->id,
            'position' => 1,
            'reason' => 'Книга поднимает вечный вопрос о цене знаний и о том, что делает нас людьми.',
            'description' => $books['cvety-dlya-elzherona']->description,
            'status' => MemberBookQueueItemStatusEnum::InVerification,
        ]);

        MemberBookQueueItem::create([
            'club_member_id' => $members['П1']->id,
            'book_id' => $books['shum-vremeni']->id,
            'position' => 2,
            'reason' => 'Роман о компромиссе и достоинстве в эпоху террора — хороший материал для дискуссии.',
            'description' => $books['shum-vremeni']->description,
            'status' => MemberBookQueueItemStatusEnum::Queued,
        ]);

        MemberBookQueueItem::create([
            'club_member_id' => $members['П1']->id,
            'book_id' => $books['oblachnyj-atlas']->id,
            'position' => 3,
            'reason' => 'Шесть переплетённых историй из разных эпох — интересно обсудить связи между ними.',
            'description' => $books['oblachnyj-atlas']->description,
            'status' => MemberBookQueueItemStatusEnum::Queued,
        ]);

        $candidate = BookCandidate::create([
            'book_id' => $books['cvety-dlya-elzherona']->id,
            'proposer_id' => $members['П1']->id,
            'member_book_queue_item_id' => $queueItem->id,
            'reason' => 'Книга поднимает вечный вопрос о цене знаний и о том, что делает нас людьми.',
            'description' => $books['cvety-dlya-elzherona']->description,
            'status' => BookCandidateStatusEnum::Pending,
        ]);

        $activeMembers = ClubMember::where('is_active', true)->get();
        foreach ($activeMembers as $member) {
            BookCandidateResponse::create([
                'book_candidate_id' => $candidate->id,
                'club_member_id' => $member->id,
                'response' => BookCandidateResponseEnum::Pending,
            ]);
        }

        $adminQueueItem1 = MemberBookQueueItem::create([
            'club_member_id' => $members['АД']->id,
            'book_id' => $books['piknik-na-obochine']->id,
            'position' => 1,
            'reason' => 'Хочется обсудить тему непостижимого и человеческую жадность перед лицом неизвестного.',
            'description' => $books['piknik-na-obochine']->description,
            'status' => MemberBookQueueItemStatusEnum::Queued,
        ]);

        MemberBookQueueItem::create([
            'club_member_id' => $members['АД']->id,
            'book_id' => $books['kratkaya-istoriya-vremeni']->id,
            'position' => 2,
            'reason' => 'Научпоп, который меняет взгляд на мир. Хороший контраст после художественной прозы.',
            'description' => $books['kratkaya-istoriya-vremeni']->description,
            'status' => MemberBookQueueItemStatusEnum::Queued,
        ]);
    }

    private function getMembers(): array
    {
        $members = [];
        foreach (ClubMember::all() as $member) {
            $members[$member->initials] = $member;
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
