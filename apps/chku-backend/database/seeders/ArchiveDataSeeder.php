<?php

namespace Database\Seeders;

use App\Enums\MeetingRsvpStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Models\Club;
use App\Models\DiscussionMessage;
use App\Models\Meeting;
use App\Models\MeetingRsvp;
use App\Models\Rating;
use App\Models\ReadingCycle;
use App\Models\Review;
use Illuminate\Database\Seeder;

class ArchiveDataSeeder extends Seeder
{
    public function run(): void
    {
        $club = Club::first();
        $books = $this->getBooks();
        $members = $this->getMembers();

        $archiveCycles = [
            ['cycle' => 1, 'book' => 'master-i-margarita', 'proposer' => 'igor@example.com', 'prompt' => 'Что в романе важнее: любовь, творчество или сатира на советское общество?', 'completed' => '2023-06-20'],
            ['cycle' => 2, 'book' => 'my', 'proposer' => 'maria@example.com', 'prompt' => 'Насколько предсказания Замятина сбылись в реальности?', 'completed' => '2023-07-18'],
            ['cycle' => 3, 'book' => 'sapiens', 'proposer' => 'pavel@example.com', 'prompt' => 'Какие обобщения книги помогают думать, а какие слишком упрощают историю?', 'completed' => '2023-08-22'],
            ['cycle' => 4, 'book' => 'process', 'proposer' => 'marina@example.com', 'prompt' => 'В чём абсурд системы: в бюрократии или в бессмысленности обвинения?', 'completed' => '2023-09-19'],
            ['cycle' => 5, 'book' => 'dumai-medlenno-reshai-bystro', 'proposer' => 'admin@example.com', 'prompt' => 'Какие когнитивные ошибки мы реально замечаем за собой после чтения?', 'completed' => '2023-10-17'],
            ['cycle' => 6, 'book' => 'solyaris', 'proposer' => 'pavel@example.com', 'prompt' => 'Возможен ли контакт, если мы всё время встречаем собственные проекции?', 'completed' => '2023-11-21'],
            ['cycle' => 7, 'book' => 'lavr', 'proposer' => 'marina@example.com', 'prompt' => 'Как язык романа меняет ощущение времени и вины?', 'completed' => '2023-12-19'],
            ['cycle' => 8, 'book' => 'duna', 'proposer' => 'admin@example.com', 'prompt' => 'Можно ли читать историю Пола как предупреждение, а не героический путь?', 'completed' => '2024-01-16'],
            ['cycle' => 9, 'book' => '1984', 'proposer' => 'pavel@example.com', 'prompt' => 'Что в романе сегодня читается политически, а что психологически?', 'completed' => '2024-02-20'],
            ['cycle' => 10, 'book' => 'temnaya-bashnya', 'proposer' => 'marina@example.com', 'prompt' => 'Что делает эту книгу сильнее обычного фэнтези?', 'completed' => '2024-03-19'],
        ];

        foreach ($archiveCycles as $cycleData) {
            $cycle = ReadingCycle::create([
                'club_id' => $club->id,
                'book_id' => $books[$cycleData['book']]->id,
                'proposer_id' => $members[$cycleData['proposer']]->id,
                'cycle_number' => $cycleData['cycle'],
                'status' => ReadingCycleStatusEnum::Completed,
                'discussion_prompt' => $cycleData['prompt'],
                'completed_at' => $cycleData['completed'],
            ]);

            Meeting::create([
                'reading_cycle_id' => $cycle->id,
                'title' => 'Встреча клуба',
                'date' => $cycleData['completed'],
                'time' => '19:00:00',
                'place' => $cycleData['cycle'] % 2 === 0 ? 'Онлайн' : 'Библиотека',
                'topics' => ['Обсуждение книги', 'Выводы и впечатления'],
                'started_at' => $cycleData['completed'].' 19:00:00',
                'finished_at' => $cycleData['completed'].' 21:00:00',
            ]);
        }

        $this->seedRatings($members);
        $this->seedReviews($members);
        $this->seedDiscussionMessages($members);
        $this->seedMeetingRsvps();
    }

    private function getBooks(): array
    {
        $books = [];
        foreach (\App\Models\Book::all() as $book) {
            $books[$book->slug] = $book;
        }
        return $books;
    }

    private function getMembers(): array
    {
        $members = [];
        foreach (\App\Models\ClubMember::with('user')->get() as $member) {
            $members[$member->user->email] = $member;
        }
        return $members;
    }

    private function seedRatings(array $members): void
    {
        $ratingsData = [
            1 => [['igor@example.com', 9], ['maria@example.com', 8], ['pavel@example.com', 9]],
            2 => [['igor@example.com', 7], ['maria@example.com', 8], ['admin@example.com', 8]],
            3 => [['pavel@example.com', 8], ['marina@example.com', 7], ['admin@example.com', 9], ['igor@example.com', 8]],
            4 => [['pavel@example.com', 7], ['marina@example.com', 8], ['admin@example.com', 7]],
            5 => [['pavel@example.com', 8], ['marina@example.com', 9], ['admin@example.com', 8]],
            6 => [['pavel@example.com', 9], ['marina@example.com', 8], ['admin@example.com', 9]],
            7 => [['pavel@example.com', 8], ['marina@example.com', 9], ['admin@example.com', 7]],
            8 => [['pavel@example.com', 9], ['marina@example.com', 8], ['admin@example.com', 9]],
            9 => [['pavel@example.com', 8], ['marina@example.com', 7], ['admin@example.com', 9]],
            10 => [['pavel@example.com', 9], ['marina@example.com', 8], ['admin@example.com', 8]],
        ];

        foreach ($ratingsData as $cycleNumber => $ratings) {
            $cycle = ReadingCycle::where('cycle_number', $cycleNumber)->first();
            foreach ($ratings as [$memberEmail, $rating]) {
                Rating::create([
                    'reading_cycle_id' => $cycle->id,
                    'club_member_id' => $members[$memberEmail]->id,
                    'rating' => $rating,
                ]);
            }
        }
    }

    private function seedReviews(array $members): void
    {
        $reviewsData = [
            1 => [
                ['igor@example.com', 'Масштабный роман, где каждая линия работает на общую тему свободы и ответственности.'],
                ['maria@example.com', 'Сатанинский бал — лучшая сцена, но и московские главы не уступают.'],
                ['pavel@example.com', 'Третий раз перечитываю и каждый раз нахожу что-то новое.'],
            ],
            2 => [
                ['igor@example.com', 'Пугающе точное предсказание, хотя некоторые детали кажутся наивными.'],
                ['maria@example.com', 'Короткий, но очень плотный роман. Ощущение безысходности передано идеально.'],
                ['admin@example.com', 'Интересно сравнивать с Оруэллом — у Замятина больше внимания к внутреннему миру.'],
            ],
            3 => [
                ['pavel@example.com', 'Не со всем согласна, но книга отлично запускает разговор о коллективных мифах.'],
                ['marina@example.com', 'Структура впечатляет, хотя некоторые главы хотелось проверять по источникам.'],
                ['admin@example.com', 'Хорошая база для спора о больших исторических обобщениях.'],
            ],
            4 => [
                ['pavel@example.com', 'Кафка создаёт ощущение, что сам процесс чтения — это тоже часть абсурда.'],
                ['marina@example.com', 'Бесконечная бюрократия, в которой теряется человек. Очень современно.'],
                ['admin@example.com', 'Мрачно, но невероятно точно описывает чувство бессилия перед системой.'],
            ],
            5 => [
                ['pavel@example.com', 'Некоторые главы сухие, зато примеры легко применить к собственным решениям.'],
                ['marina@example.com', 'Хорошо для клуба: каждый принёс личный пример ошибки мышления.'],
                ['admin@example.com', 'Практичная книга, после которой начинаешь замечать свои когнитивные ловушки.'],
            ],
            6 => [
                ['pavel@example.com', 'Сильнее всего сработала невозможность понять чужое без насилия интерпретации.'],
                ['marina@example.com', 'Медленная, но очень точная книга для разговора о памяти.'],
                ['admin@example.com', 'Отдельно обсуждали, почему научная экспедиция быстро становится исповедью.'],
            ],
            7 => [
                ['pavel@example.com', 'Очень цельный опыт: язык, сюжет и тема искупления держатся вместе.'],
                ['marina@example.com', 'Одна из самых тихих и сильных встреч клуба.'],
                ['admin@example.com', 'Понравилась мысль о том, что герой не исправляет прошлое, а несёт его.'],
            ],
            8 => [
                ['pavel@example.com', 'Масштаб впечатляет, но сильнее всего зацепили экологические детали.'],
                ['marina@example.com', 'Местами тяжеловато, зато мир ощущается цельным и очень продуманным.'],
                ['admin@example.com', 'Обсуждение ушло в вопрос о том, как харизма лидера становится ловушкой.'],
            ],
            9 => [
                ['pavel@example.com', 'Перечитывать было тревожно, но разговор получился очень точным.'],
                ['marina@example.com', 'Больше всего обсуждали язык как инструмент ограничения воображения.'],
                ['admin@example.com', 'Стоит сравнить с Замятиным в одном из следующих циклов.'],
            ],
            10 => [
                ['pavel@example.com', 'Неожиданно глубокая книга для жанра. Образы пустыни и башни работают на нескольких уровнях.'],
                ['marina@example.com', 'Местами затянуто, но финал вытягивает всё.'],
                ['admin@example.com', 'Хороший переход от серьёзной прозы к чему-то более развлекательному, но не пустому.'],
            ],
        ];

        foreach ($reviewsData as $cycleNumber => $reviews) {
            $cycle = ReadingCycle::where('cycle_number', $cycleNumber)->first();
            foreach ($reviews as [$memberEmail, $text]) {
                Review::create([
                    'reading_cycle_id' => $cycle->id,
                    'club_member_id' => $members[$memberEmail]->id,
                    'text' => $text,
                ]);
            }
        }
    }

    private function seedDiscussionMessages(array $members): void
    {
        $messagesData = [
            1 => [['igor@example.com', 'на встрече', 'Отдельно обсуждали, почему Воланд — не просто дьявол, а скорее судья.']],
            2 => [['maria@example.com', 'после встречи', 'Понравилась параллель с современными системами контроля.']],
            3 => [['pavel@example.com', 'заметка', 'В следующий раз к нон-фикшну стоит готовить список вопросов заранее.']],
            4 => [['marina@example.com', 'на встрече', 'Сошлись на том, что абсурд — это не отсутствие логики, а избыток бессмысленных правил.']],
            5 => [['admin@example.com', 'после встречи', 'Отдельно записал тему денег как доверия для будущей дискуссии.']],
            6 => [['pavel@example.com', 'на встрече', 'Обсуждали, почему научная экспедиция быстро становится исповедью.']],
            7 => [['marina@example.com', 'заметка', 'Хочется отдельно вернуться к тому, как роман показывает время не линейно.']],
            8 => [['admin@example.com', 'на встрече', 'Обсуждение ушло в вопрос о том, как экология становится политикой.']],
            9 => [['pavel@example.com', 'после встречи', 'Стоит сравнить с Замятиным — обе книги про контроль, но по-разному.']],
            10 => [['marina@example.com', 'на встрече', 'Интересно, как Кинг смешивает вестерн, фэнтези и постапокалипсис в одной истории.']],
        ];

        foreach ($messagesData as $cycleNumber => $messages) {
            $cycle = ReadingCycle::where('cycle_number', $cycleNumber)->first();
            foreach ($messages as [$memberEmail, $label, $text]) {
                DiscussionMessage::create([
                    'reading_cycle_id' => $cycle->id,
                    'club_member_id' => $members[$memberEmail]->id,
                    'text' => $text,
                    'context_label' => $label,
                ]);
            }
        }
    }

    private function seedMeetingRsvps(): void
    {
        $meetings = Meeting::all();
        $activeMembers = \App\Models\ClubMember::whereHas(
            'user',
            fn ($query) => $query->whereIn('email', ['pavel@example.com', 'marina@example.com', 'admin@example.com']),
        )->get();

        foreach ($meetings as $meeting) {
            foreach ($activeMembers as $member) {
                MeetingRsvp::create([
                    'meeting_id' => $meeting->id,
                    'club_member_id' => $member->id,
                    'status' => MeetingRsvpStatusEnum::Attending,
                ]);
            }
        }
    }
}
