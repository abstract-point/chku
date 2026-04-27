<?php

namespace Database\Seeders;

use App\Enums\BookCandidateResponseEnum;
use App\Enums\BookCandidateStatusEnum;
use App\Enums\MeetingRsvpStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Models\Book;
use App\Models\BookCandidate;
use App\Models\BookCandidateResponse;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\DiscussionMessage;
use App\Models\Genre;
use App\Models\Meeting;
use App\Models\MeetingRsvp;
use App\Models\Rating;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use App\Models\Review;
use App\Models\TurnOrder;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $club = Club::create([
            'name' => 'Читальный клуб умничек',
            'short_name' => 'ЧКУ',
        ]);

        $genres = [
            ['slug' => 'fiction', 'name' => 'Проза'],
            ['slug' => 'nonfiction', 'name' => 'Нон-фикшн'],
            ['slug' => 'scifi', 'name' => 'Фантастика'],
        ];

        foreach ($genres as $genreData) {
            Genre::create($genreData);
        }

        $membersData = [
            ['name' => 'Елена Воронцова', 'initials' => 'ЕЛ', 'email' => 'elena@example.com', 'joined' => '2021-03-15', 'active' => true, 'genre' => 'fiction'],
            ['name' => 'Михаил Корнев', 'initials' => 'МК', 'email' => 'mikhail@example.com', 'joined' => '2021-01-10', 'active' => true, 'genre' => 'scifi'],
            ['name' => 'Анна Соколова', 'initials' => 'АС', 'email' => 'anna@example.com', 'joined' => '2022-06-20', 'active' => true, 'genre' => 'nonfiction'],
            ['name' => 'Тимур Васильев', 'initials' => 'ТВ', 'email' => 'timur@example.com', 'joined' => '2022-09-01', 'active' => true, 'genre' => 'fiction'],
            ['name' => 'Ольга Петрова', 'initials' => 'ОП', 'email' => 'olga@example.com', 'joined' => '2021-05-05', 'active' => false, 'genre' => 'fiction'],
            ['name' => 'Дмитрий Смирнов', 'initials' => 'ДС', 'email' => 'dmitry@example.com', 'joined' => '2020-11-12', 'active' => false, 'genre' => 'nonfiction'],
        ];

        $members = [];
        foreach ($membersData as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt('password'),
            ]);

            $members[$data['initials']] = ClubMember::create([
                'club_id' => $club->id,
                'user_id' => $user->id,
                'initials' => $data['initials'],
                'is_active' => $data['active'],
                'joined_at' => $data['joined'],
                'favorite_genre_id' => Genre::where('slug', $data['genre'])->value('id'),
            ]);

            $user->assignRole('member');
        }

        $booksData = [
            ['title' => 'Солярис', 'author' => 'Станислав Лем', 'slug' => 'solyaris', 'genre' => 'scifi', 'color' => '#46656b', 'desc' => 'Контакт с внеземным океаном оказывается разговором не с другим разумом, а с собственными памятью, стыдом и любовью.'],
            ['title' => 'Лавр', 'author' => 'Евгений Водолазкин', 'slug' => 'lavr', 'genre' => 'fiction', 'color' => '#7b5f4a', 'desc' => 'Роман о страннике Арсении, который проходит путь любви, вины и служения в мире, где время течёт не линейно.'],
            ['title' => 'Думай медленно... решай быстро', 'author' => 'Даниэль Канеман', 'slug' => 'dumai-medlenno-reshai-bystro', 'genre' => 'nonfiction', 'color' => '#6f7f4b', 'desc' => 'Исследование двух режимов мышления и когнитивных искажений, которые влияют на решения в быту, работе и обществе.'],
            ['title' => 'Франкенштейн', 'author' => 'Мэри Шелли', 'slug' => 'frankenshtein', 'genre' => 'fiction', 'color' => '#6b5b95', 'desc' => 'История создателя и созданного им существа, в которой научное дерзновение сталкивается с одиночеством и отказом от ответственности.'],
            ['title' => '1984', 'author' => 'Джордж Оруэлл', 'slug' => '1984', 'genre' => 'fiction', 'color' => '#2c363f', 'desc' => 'Антиутопия о языке, страхе и государственном контроле, где частная мысль становится последним пространством сопротивления.'],
            ['title' => 'Sapiens. Краткая история человечества', 'author' => 'Юваль Ной Харари', 'slug' => 'sapiens', 'genre' => 'nonfiction', 'color' => '#8c5e58', 'desc' => 'Популярный обзор истории Homo sapiens от когнитивной революции до современных институтов, технологий и мифов.'],
            ['title' => 'Дюна', 'author' => 'Фрэнк Герберт', 'slug' => 'duna', 'genre' => 'scifi', 'color' => '#4a5d4e', 'desc' => 'Политика, экология и религиозный миф сталкиваются на пустынной планете Арракис, где власть зависит от единственного ресурса.'],
            ['title' => 'Тайная история', 'author' => 'Донна Тартт', 'slug' => 'taynaya-istoriya', 'genre' => 'fiction', 'color' => '#3a405a', 'desc' => 'Закрытый круг студентов-классиков в маленьком колледже постепенно превращает эстетическое увлечение древностью в моральную катастрофу.'],
            ['title' => 'Тень ветра', 'author' => 'Карлос Руис Сафон', 'slug' => 'ten-vetra', 'genre' => 'fiction', 'color' => '#5a4a3a', 'desc' => 'В послевоенной Барселоне мальчик находит таинственную книгу на Кладбище забытых книг. Эта находка ведёт его в историю о памяти, любви и старых тайнах города.'],
        ];

        $books = [];
        foreach ($booksData as $data) {
            $books[$data['slug']] = Book::create([
                'title' => $data['title'],
                'author' => $data['author'],
                'slug' => $data['slug'],
                'genre_id' => Genre::where('slug', $data['genre'])->value('id'),
                'description' => $data['desc'],
                'cover_color' => $data['color'],
            ]);
        }

        $archiveCycles = [
            ['cycle' => 34, 'book' => 'solyaris', 'proposer' => 'АС', 'prompt' => 'Возможен ли контакт, если мы всё время встречаем собственные проекции?', 'completed' => '2023-02-19'],
            ['cycle' => 35, 'book' => 'lavr', 'proposer' => 'ОП', 'prompt' => 'Как язык романа меняет ощущение времени и вины?', 'completed' => '2023-03-19'],
            ['cycle' => 36, 'book' => 'dumai-medlenno-reshai-bystro', 'proposer' => 'МК', 'prompt' => 'Какие когнитивные ошибки мы реально замечаем за собой после чтения?', 'completed' => '2023-04-23'],
            ['cycle' => 37, 'book' => 'frankenshtein', 'proposer' => 'ЕЛ', 'prompt' => 'Кто в романе оказывается более человечным: Виктор или его создание?', 'completed' => '2023-05-21'],
            ['cycle' => 38, 'book' => '1984', 'proposer' => 'ОП', 'prompt' => 'Что в романе сегодня читается политически, а что психологически?', 'completed' => '2023-06-18'],
            ['cycle' => 39, 'book' => 'sapiens', 'proposer' => 'АС', 'prompt' => 'Какие обобщения книги помогают думать, а какие слишком упрощают историю?', 'completed' => '2023-07-16'],
            ['cycle' => 40, 'book' => 'duna', 'proposer' => 'МК', 'prompt' => 'Можно ли читать историю Пола как предупреждение, а не героический путь?', 'completed' => '2023-08-20'],
            ['cycle' => 41, 'book' => 'taynaya-istoriya', 'proposer' => 'ЕЛ', 'prompt' => 'Где проходит граница между интеллектуальной игрой и ответственностью за поступок?', 'completed' => '2023-09-24'],
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

        $currentCycle = ReadingCycle::create([
            'club_id' => $club->id,
            'book_id' => $books['ten-vetra']->id,
            'proposer_id' => $members['МК']->id,
            'cycle_number' => 42,
            'status' => ReadingCycleStatusEnum::Active,
        ]);

        Meeting::create([
            'reading_cycle_id' => $currentCycle->id,
            'title' => 'Октябрьская встреча',
            'date' => '2023-10-18',
            'time' => '19:00:00',
            'place' => 'Библиотека имени Некрасова, зал «Сад»',
            'address' => 'ул. Литературная, 123, центр',
            'reservation' => 'Забронировано на имя «ЧКУ»',
            'link' => 'zoom.us/j/chku-meeting',
            'topics' => [
                'Значение Кладбища забытых книг.',
                'Прогрессия характера Даниэля и параллели с Хулианом Караксом.',
                'Изображение послевоенной Барселоны как самостоятельного персонажа.',
                'Темы памяти, одержимости и силы литературы.',
            ],
        ]);

        foreach ($members as $member) {
            ReadingProgress::create([
                'reading_cycle_id' => $currentCycle->id,
                'club_member_id' => $member->id,
                'status' => match ($member->initials) {
                    'ЕЛ' => ReadingProgressStatusEnum::Finished,
                    'МК' => ReadingProgressStatusEnum::Reading,
                    default => ReadingProgressStatusEnum::NotStarted,
                },
                'progress_percent' => match ($member->initials) {
                    'ЕЛ' => 100,
                    'МК' => 80,
                    default => null,
                },
            ]);
        }

        foreach ($members as $member) {
            MeetingRsvp::create([
                'meeting_id' => Meeting::where('reading_cycle_id', $currentCycle->id)->value('id'),
                'club_member_id' => $member->id,
                'status' => MeetingRsvpStatusEnum::Pending,
            ]);
        }

        $candidate = BookCandidate::create([
            'book_id' => $books['taynaya-istoriya']->id,
            'proposer_id' => $members['ЕЛ']->id,
            'reason' => 'Подойдёт для обсуждения ответственности, дружбы и того, как интеллектуальная среда меняет людей.',
            'description' => 'Роман о закрытом круге студентов, античной культуре и последствиях одного решения.',
            'status' => BookCandidateStatusEnum::Pending,
        ]);

        foreach ($members as $member) {
            if (! $member->is_active) {
                continue;
            }

            BookCandidateResponse::create([
                'book_candidate_id' => $candidate->id,
                'club_member_id' => $member->id,
                'response' => BookCandidateResponseEnum::Pending,
            ]);
        }

        TurnOrder::create(['club_id' => $club->id, 'club_member_id' => $members['МК']->id, 'position' => 1, 'is_current' => true]);
        TurnOrder::create(['club_id' => $club->id, 'club_member_id' => $members['ЕЛ']->id, 'position' => 2, 'is_next' => true]);
        TurnOrder::create(['club_id' => $club->id, 'club_member_id' => $members['АС']->id, 'position' => 3]);
        TurnOrder::create(['club_id' => $club->id, 'club_member_id' => $members['ТВ']->id, 'position' => 4]);

        $ratingsData = [
            34 => [['ЕЛ', 9], ['ОП', 8]],
            35 => [['ОП', 9], ['ЕЛ', 9]],
            36 => [['МК', 8], ['АС', 8]],
            37 => [['ЕЛ', 8], ['МК', 7]],
            38 => [['ОП', 9], ['АС', 9]],
            39 => [['АС', 8], ['ЕЛ', 7]],
            40 => [['МК', 9], ['ОП', 8]],
            41 => [['ЕЛ', 9], ['МК', 10]],
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

        $reviewsData = [
            34 => [
                ['АС', 'Сильнее всего сработала невозможность понять чужое без насилия интерпретации.'],
                ['ОП', 'Медленная, но очень точная книга для разговора о памяти.'],
            ],
            35 => [
                ['ОП', 'Очень цельный опыт: язык, сюжет и тема искупления держатся вместе.'],
                ['ЕЛ', 'Одна из самых тихих и сильных встреч клуба.'],
            ],
            36 => [
                ['МК', 'Некоторые главы сухие, зато примеры легко применить к собственным решениям.'],
                ['АС', 'Хорошо для клуба: каждый принёс личный пример ошибки мышления.'],
            ],
            37 => [
                ['ЕЛ', 'Книга совсем не про монстра в привычном смысле, а про оставленность.'],
                ['МК', 'Понравилась рамочная структура, но эмоционально не всё сработало.'],
            ],
            38 => [
                ['ОП', 'Перечитывать было тревожно, но разговор получился очень точным.'],
                ['АС', 'Больше всего обсуждали язык как инструмент ограничения воображения.'],
            ],
            39 => [
                ['АС', 'Не со всем согласна, но книга отлично запускает разговор о коллективных мифах.'],
                ['ЕЛ', 'Понравилась структура, хотя некоторые главы хотелось проверять по дополнительным источникам.'],
            ],
            40 => [
                ['МК', 'Масштаб впечатляет, но сильнее всего зацепили экологические детали и цена пророчества.'],
                ['ОП', 'Местами тяжеловато, зато мир ощущается цельным и очень продуманным.'],
            ],
            41 => [
                ['ЕЛ', 'Очень плотная книга для обсуждения: атмосфера держит, а герои вызывают споры почти в каждой сцене.'],
                ['МК', 'Лучший разговор сезона. Особенно хорошо сработала тема избранности и самообмана.'],
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

        $messagesData = [
            34 => [['ЕЛ', 'на встрече', 'Отдельно обсуждали, почему научная экспедиция быстро становится исповедью.']],
            35 => [['МК', 'после встречи', 'Понравилась мысль о том, что герой не исправляет прошлое, а несёт его.']],
            36 => [['ЕЛ', 'заметка', 'В следующий раз к нон-фикшну стоит готовить список вопросов заранее.']],
            37 => [['ОП', 'на встрече', 'Сошлись на том, что роман лучше обсуждать через тему родительства.']],
            38 => [['ЕЛ', 'заметка', 'Стоит сравнить с Замятиным в одном из следующих циклов.']],
            39 => [['МК', 'после встречи', 'Отдельно записал тему денег как доверия для будущей дискуссии.']],
            40 => [['ЕЛ', 'на встрече', 'Обсуждение ушло в вопрос о том, как харизма лидера становится ловушкой для общества.']],
            41 => [['АС', 'после встречи', 'Хочется отдельно вернуться к тому, как роман показывает дружбу как зависимость.']],
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
}
