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
            ['cycle' => 1, 'book' => 'master-i-margarita', 'proposer' => 'Н1', 'prompt' => 'Что в романе важнее: любовь, творчество или сатира на советское общество?', 'completed' => '2023-06-20'],
            ['cycle' => 2, 'book' => 'my', 'proposer' => 'Н2', 'prompt' => 'Насколько предсказания Замятина сбылись в реальности?', 'completed' => '2023-07-18'],
            ['cycle' => 3, 'book' => 'sapiens', 'proposer' => 'П1', 'prompt' => 'Какие обобщения книги помогают думать, а какие слишком упрощают историю?', 'completed' => '2023-08-22'],
            ['cycle' => 4, 'book' => 'process', 'proposer' => 'П2', 'prompt' => 'В чём абсурд системы: в бюрократии или в бессмысленности обвинения?', 'completed' => '2023-09-19'],
            ['cycle' => 5, 'book' => 'dumai-medlenno-reshai-bystro', 'proposer' => 'АД', 'prompt' => 'Какие когнитивные ошибки мы реально замечаем за собой после чтения?', 'completed' => '2023-10-17'],
            ['cycle' => 6, 'book' => 'solyaris', 'proposer' => 'П1', 'prompt' => 'Возможен ли контакт, если мы всё время встречаем собственные проекции?', 'completed' => '2023-11-21'],
            ['cycle' => 7, 'book' => 'lavr', 'proposer' => 'П2', 'prompt' => 'Как язык романа меняет ощущение времени и вины?', 'completed' => '2023-12-19'],
            ['cycle' => 8, 'book' => 'duna', 'proposer' => 'АД', 'prompt' => 'Можно ли читать историю Пола как предупреждение, а не героический путь?', 'completed' => '2024-01-16'],
            ['cycle' => 9, 'book' => '1984', 'proposer' => 'П1', 'prompt' => 'Что в романе сегодня читается политически, а что психологически?', 'completed' => '2024-02-20'],
            ['cycle' => 10, 'book' => 'temnaya-bashnya', 'proposer' => 'П2', 'prompt' => 'Что делает эту книгу сильнее обычного фэнтези?', 'completed' => '2024-03-19'],
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
        foreach (\App\Models\ClubMember::all() as $member) {
            $members[$member->initials] = $member;
        }
        return $members;
    }

    private function seedRatings(array $members): void
    {
        $ratingsData = [
            1 => [['Н1', 9], ['Н2', 8], ['П1', 9]],
            2 => [['Н1', 7], ['Н2', 8], ['АД', 8]],
            3 => [['П1', 8], ['П2', 7], ['АД', 9], ['Н1', 8]],
            4 => [['П1', 7], ['П2', 8], ['АД', 7]],
            5 => [['П1', 8], ['П2', 9], ['АД', 8]],
            6 => [['П1', 9], ['П2', 8], ['АД', 9]],
            7 => [['П1', 8], ['П2', 9], ['АД', 7]],
            8 => [['П1', 9], ['П2', 8], ['АД', 9]],
            9 => [['П1', 8], ['П2', 7], ['АД', 9]],
            10 => [['П1', 9], ['П2', 8], ['АД', 8]],
        ];

        foreach ($ratingsData as $cycleNumber => $ratings) {
            $cycle = ReadingCycle::where('cycle_number', $cycleNumber)->first();
            foreach ($ratings as [$initials, $rating]) {
                Rating::create([
                    'reading_cycle_id' => $cycle->id,
                    'club_member_id' => $members[$initials]->id,
                    'rating' => $rating,
                ]);
            }
        }
    }

    private function seedReviews(array $members): void
    {
        $reviewsData = [
            1 => [
                ['Н1', 'Масштабный роман, где каждая линия работает на общую тему свободы и ответственности.'],
                ['Н2', 'Сатанинский бал — лучшая сцена, но и московские главы не уступают.'],
                ['П1', 'Третий раз перечитываю и каждый раз нахожу что-то новое.'],
            ],
            2 => [
                ['Н1', 'Пугающе точное предсказание, хотя некоторые детали кажутся наивными.'],
                ['Н2', 'Короткий, но очень плотный роман. Ощущение безысходности передано идеально.'],
                ['АД', 'Интересно сравнивать с Оруэллом — у Замятина больше внимания к внутреннему миру.'],
            ],
            3 => [
                ['П1', 'Не со всем согласна, но книга отлично запускает разговор о коллективных мифах.'],
                ['П2', 'Структура впечатляет, хотя некоторые главы хотелось проверять по источникам.'],
                ['АД', 'Хорошая база для спора о больших исторических обобщениях.'],
            ],
            4 => [
                ['П1', 'Кафка создаёт ощущение, что сам процесс чтения — это тоже часть абсурда.'],
                ['П2', 'Бесконечная бюрократия, в которой теряется человек. Очень современно.'],
                ['АД', 'Мрачно, но невероятно точно описывает чувство бессилия перед системой.'],
            ],
            5 => [
                ['П1', 'Некоторые главы сухие, зато примеры легко применить к собственным решениям.'],
                ['П2', 'Хорошо для клуба: каждый принёс личный пример ошибки мышления.'],
                ['АД', 'Практичная книга, после которой начинаешь замечать свои когнитивные ловушки.'],
            ],
            6 => [
                ['П1', 'Сильнее всего сработала невозможность понять чужое без насилия интерпретации.'],
                ['П2', 'Медленная, но очень точная книга для разговора о памяти.'],
                ['АД', 'Отдельно обсуждали, почему научная экспедиция быстро становится исповедью.'],
            ],
            7 => [
                ['П1', 'Очень цельный опыт: язык, сюжет и тема искупления держатся вместе.'],
                ['П2', 'Одна из самых тихих и сильных встреч клуба.'],
                ['АД', 'Понравилась мысль о том, что герой не исправляет прошлое, а несёт его.'],
            ],
            8 => [
                ['П1', 'Масштаб впечатляет, но сильнее всего зацепили экологические детали.'],
                ['П2', 'Местами тяжеловато, зато мир ощущается цельным и очень продуманным.'],
                ['АД', 'Обсуждение ушло в вопрос о том, как харизма лидера становится ловушкой.'],
            ],
            9 => [
                ['П1', 'Перечитывать было тревожно, но разговор получился очень точным.'],
                ['П2', 'Больше всего обсуждали язык как инструмент ограничения воображения.'],
                ['АД', 'Стоит сравнить с Замятиным в одном из следующих циклов.'],
            ],
            10 => [
                ['П1', 'Неожиданно глубокая книга для жанра. Образы пустыни и башни работают на нескольких уровнях.'],
                ['П2', 'Местами затянуто, но финал вытягивает всё.'],
                ['АД', 'Хороший переход от серьёзной прозы к чему-то более развлекательному, но не пустому.'],
            ],
        ];

        foreach ($reviewsData as $cycleNumber => $reviews) {
            $cycle = ReadingCycle::where('cycle_number', $cycleNumber)->first();
            foreach ($reviews as [$initials, $text]) {
                Review::create([
                    'reading_cycle_id' => $cycle->id,
                    'club_member_id' => $members[$initials]->id,
                    'text' => $text,
                ]);
            }
        }
    }

    private function seedDiscussionMessages(array $members): void
    {
        $messagesData = [
            1 => [['Н1', 'на встрече', 'Отдельно обсуждали, почему Воланд — не просто дьявол, а скорее судья.']],
            2 => [['Н2', 'после встречи', 'Понравилась параллель с современными системами контроля.']],
            3 => [['П1', 'заметка', 'В следующий раз к нон-фикшну стоит готовить список вопросов заранее.']],
            4 => [['П2', 'на встрече', 'Сошлись на том, что абсурд — это не отсутствие логики, а избыток бессмысленных правил.']],
            5 => [['АД', 'после встречи', 'Отдельно записал тему денег как доверия для будущей дискуссии.']],
            6 => [['П1', 'на встрече', 'Обсуждали, почему научная экспедиция быстро становится исповедью.']],
            7 => [['П2', 'заметка', 'Хочется отдельно вернуться к тому, как роман показывает время не линейно.']],
            8 => [['АД', 'на встрече', 'Обсуждение ушло в вопрос о том, как экология становится политикой.']],
            9 => [['П1', 'после встречи', 'Стоит сравнить с Замятиным — обе книги про контроль, но по-разному.']],
            10 => [['П2', 'на встрече', 'Интересно, как Кинг смешивает вестерн, фэнтези и постапокалипсис в одной истории.']],
        ];

        foreach ($messagesData as $cycleNumber => $messages) {
            $cycle = ReadingCycle::where('cycle_number', $cycleNumber)->first();
            foreach ($messages as [$initials, $label, $text]) {
                DiscussionMessage::create([
                    'reading_cycle_id' => $cycle->id,
                    'club_member_id' => $members[$initials]->id,
                    'text' => $text,
                    'context_label' => $label,
                ]);
            }
        }
    }

    private function seedMeetingRsvps(): void
    {
        $meetings = Meeting::all();
        $activeMembers = \App\Models\ClubMember::where('is_active', true)->get();

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
