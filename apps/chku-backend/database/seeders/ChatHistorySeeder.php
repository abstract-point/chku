<?php

namespace Database\Seeders;

use App\Enums\MeetingRsvpStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Models\Book;
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
use App\Services\TurnOrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ChatHistorySeeder extends Seeder
{
    private Club $club;
    private array $users = [];
    private array $members = [];
    private array $books = [];

    public function run(): void
    {
        $this->seedPermissions();
        $this->seedClubAndGenres();
        $this->seedUsersAndMembers();
        $this->seedBooks();
        $this->seedTurnOrder();
        $this->seedCycle1SamiBogi();
        $this->seedCycle2ZashitaLuzhina();
        $this->seedCycle3Ten();
    }

    private function memberPassword(): string
    {
        return Hash::make(env('CHKU_DEVELOPER_PASSWORD', 'password'));
    }

    private function seedPermissions(): void
    {
        $permissions = [
            'club_members.create',
            'club_members.deactivate',
            'meetings.create',
            'meetings.update',
            'audit_logs.view',
            'developer_tools.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $developer = Role::firstOrCreate(['name' => 'developer', 'guard_name' => 'web']);

        $admin->syncPermissions([
            'club_members.create',
            'club_members.deactivate',
            'meetings.create',
            'meetings.update',
        ]);

        $developer->syncPermissions([
            'club_members.create',
            'club_members.deactivate',
            'meetings.create',
            'meetings.update',
            'audit_logs.view',
            'developer_tools.view',
        ]);
    }

    private function seedClubAndGenres(): void
    {
        $this->club = Club::firstOrCreate(
            ['short_name' => 'ЧКУ'],
            ['name' => 'Читальный клуб умничек'],
        );

        $genres = [
            ['slug' => 'fiction', 'name' => 'Проза'],
            ['slug' => 'nonfiction', 'name' => 'Нон-фикшн'],
            ['slug' => 'scifi', 'name' => 'Фантастика'],
        ];

        foreach ($genres as $genreData) {
            Genre::firstOrCreate(['slug' => $genreData['slug']], $genreData);
        }
    }

    private function seedUsersAndMembers(): void
    {
        $membersData = [
            [
                'name' => 'Анастасия Лысенко',
                'email' => 'nastya@chku.local',
                'joined' => '2026-02-01',
                'active' => true,
                'genre' => 'fiction',
                'role' => 'admin',
            ],
            [
                'name' => 'Иван Лысенко',
                'email' => 'ivan@chku.local',
                'joined' => '2026-02-01',
                'active' => true,
                'genre' => 'fiction',
                'role' => 'admin',
            ],
            [
                'name' => 'Максим Хомяков',
                'email' => 'maxim@chku.local',
                'joined' => '2026-02-01',
                'active' => false,
                'deactivated_at' => '2026-03-10 00:00:00',
                'genre' => 'scifi',
                'role' => 'member',
            ],
            [
                'name' => 'Денис Перов',
                'email' => 'denis@chku.local',
                'joined' => '2026-02-16',
                'active' => true,
                'genre' => 'nonfiction',
                'role' => 'member',
            ],
            [
                'name' => 'Александр Семёнов',
                'email' => 'alexander@chku.local',
                'joined' => '2026-04-06',
                'active' => true,
                'genre' => 'fiction',
                'role' => 'member',
            ],
            [
                'name' => 'Андрей Семёнов',
                'email' => 'andrey@chku.local',
                'joined' => '2026-05-11',
                'active' => true,
                'genre' => 'fiction',
                'role' => 'member',
            ],
        ];

        foreach ($membersData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $this->memberPassword(),
                ],
            );

            if ($user->wasRecentlyCreated && $user->email_verified_at === null) {
                $user->email_verified_at = now();
                $user->save();
            }

            $member = ClubMember::firstOrCreate(
                ['club_id' => $this->club->id, 'user_id' => $user->id],
                [
                    'is_active' => $data['active'],
                    'deactivated_at' => $data['deactivated_at'] ?? null,
                    'joined_at' => $data['joined'],
                    'favorite_genre_id' => Genre::where('slug', $data['genre'])->value('id'),
                ],
            );

            if (! $member->wasRecentlyCreated) {
                $member->update(['is_active' => $data['active']]);
            }

            $user->syncRoles([$data['role']]);

            $this->users[$data['email']] = $user;
            $this->members[$data['email']] = $member;
        }
    }

    private function seedBooks(): void
    {
        $booksData = [
            [
                'title' => 'Сами Боги',
                'author' => 'Айзек Азимов',
                'slug' => 'sami-bogi',
                'genre' => 'scifi',
                'color' => '#3d5a5a',
                'desc' => 'Роман о контакте с внеземной цивилизацией из параллельной вселенной, где законы физики отличаются от наших.',
            ],
            [
                'title' => 'Защита Лужина',
                'author' => 'Владимир Набоков',
                'slug' => 'zashita-luzhina',
                'genre' => 'fiction',
                'color' => '#4a3b4a',
                'desc' => 'История гениального шахматиста, для которого игра становится единственной реальностью, а внешний мир — угрозой.',
            ],
            [
                'title' => 'Тень',
                'author' => 'Иван Филиппов',
                'slug' => 'ten',
                'genre' => 'fiction',
                'color' => '#2a2a3a',
                'desc' => 'Лёгкое чтиво — роман о тайнах и тёмных сторонах человеческой души.',
            ],
        ];

        foreach ($booksData as $data) {
            $book = Book::firstOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'author' => $data['author'],
                    'genre_id' => Genre::where('slug', $data['genre'])->value('id'),
                    'description' => $data['desc'],
                    'cover_color' => $data['color'],
                ],
            );

            $this->books[$data['slug']] = $book;
        }
    }

    private function seedTurnOrder(): void
    {
        $existingCount = TurnOrder::where('club_id', $this->club->id)->count();
        if ($existingCount > 0) {
            return;
        }

        $activeEmails = ['nastya@chku.local', 'ivan@chku.local', 'denis@chku.local', 'alexander@chku.local', 'andrey@chku.local'];
        $previous = null;

        foreach ($activeEmails as $email) {
            $member = $this->members[$email] ?? null;
            if (! $member) {
                continue;
            }

            $order = TurnOrder::create([
                'club_id' => $this->club->id,
                'club_member_id' => $member->id,
                'next_turn_order_id' => null,
            ]);

            $previous?->update(['next_turn_order_id' => $order->id]);
            $previous = $order;
        }
    }

    private function seedCycle1SamiBogi(): void
    {
        if (ReadingCycle::where('club_id', $this->club->id)->where('cycle_number', 1)->exists()) {
            return;
        }

        $cycle = ReadingCycle::create([
            'club_id' => $this->club->id,
            'book_id' => $this->books['sami-bogi']->id,
            'proposer_id' => $this->members['maxim@chku.local']->id,
            'cycle_number' => 1,
            'status' => ReadingCycleStatusEnum::Completed,
            'completed_at' => '2026-03-09 22:00:00',
        ]);

        $meeting = Meeting::create([
            'reading_cycle_id' => $cycle->id,
            'title' => 'Обсуждение книги',
            'date' => '2026-03-09',
            'time' => '21:00:00',
            'place' => 'Онлайн',
            'link' => 'https://telemost.yandex.ru/',
            'is_online' => true,

            'started_at' => '2026-03-09 21:00:00',
            'finished_at' => '2026-03-09 22:00:00',
        ]);

        $activeMemberEmails = ['nastya@chku.local', 'ivan@chku.local', 'denis@chku.local'];
        foreach ($activeMemberEmails as $email) {
            MeetingRsvp::create([
                'meeting_id' => $meeting->id,
                'club_member_id' => $this->members[$email]->id,
                'status' => MeetingRsvpStatusEnum::Attending,
            ]);
        }

        $progressData = [
            'nastya@chku.local' => ['status' => ReadingProgressStatusEnum::Finished, 'percent' => 100, 'finished_at' => '2026-02-16 00:00:00'],
            'ivan@chku.local' => ['status' => ReadingProgressStatusEnum::Finished, 'percent' => 100, 'finished_at' => '2026-03-05 00:00:00'],
            'denis@chku.local' => ['status' => ReadingProgressStatusEnum::Finished, 'percent' => 100, 'finished_at' => '2026-03-05 00:00:00'],
        ];

        foreach ($progressData as $email => $data) {
            ReadingProgress::create([
                'reading_cycle_id' => $cycle->id,
                'club_member_id' => $this->members[$email]->id,
                'status' => $data['status'],
                'progress_percent' => $data['percent'],
                'finished_at' => $data['finished_at'],
            ]);
        }

        $ratingsData = [
            ['nastya@chku.local', 7.5],
            ['ivan@chku.local', 8],
            ['denis@chku.local', 6],
        ];

        foreach ($ratingsData as [$email, $rating]) {
            Rating::create([
                'reading_cycle_id' => $cycle->id,
                'club_member_id' => $this->members[$email]->id,
                'rating' => $rating,
            ]);
        }

        $reviewsData = [
            ['nastya@chku.local', 'Азимов мастерски строит мир с альтернативной физикой. Вторая часть романа — самая сильная и глубокая.'],
            ['ivan@chku.local', 'Интересная концепция контакта с внеземной цивилизацией. Параллельная вселенная прописана убедительно.'],
            ['denis@chku.local', 'Первая часть шла тяжеловато, но вторая и третья вытянули. Рад что дочитал.'],
        ];

        foreach ($reviewsData as [$email, $text]) {
            Review::create([
                'reading_cycle_id' => $cycle->id,
                'club_member_id' => $this->members[$email]->id,
                'text' => $text,
            ]);
        }

        DiscussionMessage::create([
            'reading_cycle_id' => $cycle->id,
            'club_member_id' => $this->members['ivan@chku.local']->id,
            'text' => 'Первое правило клуба: выбирай книгу, которая понравилась бы тебе и другим участникам клуба.',
        ]);

        DiscussionMessage::create([
            'reading_cycle_id' => $cycle->id,
            'club_member_id' => $this->members['nastya@chku.local']->id,
            'text' => 'Второе правило клуба: запрещено перебивать члена клуба во время его выступления. Другие участники могут высказаться после завершения выступления.',
        ]);

        app(TurnOrderService::class)->rotateAfterCompletedCycle($cycle);
    }

    private function seedCycle2ZashitaLuzhina(): void
    {
        if (ReadingCycle::where('club_id', $this->club->id)->where('cycle_number', 2)->exists()) {
            return;
        }

        $cycle = ReadingCycle::create([
            'club_id' => $this->club->id,
            'book_id' => $this->books['zashita-luzhina']->id,
            'proposer_id' => $this->members['nastya@chku.local']->id,
            'cycle_number' => 2,
            'status' => ReadingCycleStatusEnum::Completed,
            'completed_at' => '2026-04-24 21:00:00',
        ]);

        $meeting = Meeting::create([
            'reading_cycle_id' => $cycle->id,
            'title' => 'Обсуждение книги',
            'date' => '2026-04-24',
            'time' => '19:30:00',
            'place' => 'Онлайн',
            'link' => 'https://telemost.yandex.ru/',
            'is_online' => true,

            'started_at' => '2026-04-24 19:30:00',
            'finished_at' => '2026-04-24 21:00:00',
        ]);

        $attendeeEmails = ['nastya@chku.local', 'ivan@chku.local', 'denis@chku.local', 'alexander@chku.local'];
        foreach ($attendeeEmails as $email) {
            MeetingRsvp::create([
                'meeting_id' => $meeting->id,
                'club_member_id' => $this->members[$email]->id,
                'status' => MeetingRsvpStatusEnum::Attending,
            ]);
        }

        $progressData = [
            'nastya@chku.local' => ['status' => ReadingProgressStatusEnum::Finished, 'percent' => 100, 'finished_at' => '2026-03-12 00:00:00'],
            'ivan@chku.local' => ['status' => ReadingProgressStatusEnum::Finished, 'percent' => 100, 'finished_at' => '2026-04-20 00:00:00'],
            'denis@chku.local' => ['status' => ReadingProgressStatusEnum::Finished, 'percent' => 100, 'finished_at' => '2026-04-01 00:00:00'],
            'alexander@chku.local' => ['status' => ReadingProgressStatusEnum::Finished, 'percent' => 100, 'finished_at' => '2026-04-06 00:00:00'],
        ];

        foreach ($progressData as $email => $data) {
            ReadingProgress::create([
                'reading_cycle_id' => $cycle->id,
                'club_member_id' => $this->members[$email]->id,
                'status' => $data['status'],
                'progress_percent' => $data['percent'],
                'finished_at' => $data['finished_at'],
            ]);
        }

        $ratingsData = [
            ['nastya@chku.local', 8],
            ['ivan@chku.local', 8.5],
            ['alexander@chku.local', 6],
            ['denis@chku.local', 5],
        ];

        foreach ($ratingsData as [$email, $rating]) {
            Rating::create([
                'reading_cycle_id' => $cycle->id,
                'club_member_id' => $this->members[$email]->id,
                'rating' => $rating,
            ]);
        }

        $reviewsData = [
            ['nastya@chku.local', 'Набоков гениально передаёт внутренний мир шахматиста. Трагедия человека, не способного адаптироваться к реальности вне игры.'],
            ['ivan@chku.local', 'Глубокая книга о гении и безумии. Язык Набокова потрясающий, каждый образ работает на общую тему.'],
            ['alexander@chku.local', 'Сложно входил в текст Набокова, но финал впечатлил. Пересмотрел оценку в сторону повышения после размышлений.'],
            ['denis@chku.local', 'Не моё. Красивый язык, но слишком депрессивно. При этом уважаю мастерство автора.'],
        ];

        foreach ($reviewsData as [$email, $text]) {
            Review::create([
                'reading_cycle_id' => $cycle->id,
                'club_member_id' => $this->members[$email]->id,
                'text' => $text,
            ]);
        }

        DiscussionMessage::create([
            'reading_cycle_id' => $cycle->id,
            'club_member_id' => $this->members['nastya@chku.local']->id,
            'text' => 'Через системность и стабильность мы формируем опоры в нашем читальном клубе. Чтобы с каждой книгой мы открывали друг друга с новых сторон.',
        ]);

        DiscussionMessage::create([
            'reading_cycle_id' => $cycle->id,
            'club_member_id' => $this->members['alexander@chku.local']->id,
            'text' => 'Чем больше людей — тем больше разных мнений! Клуб расширяется, это здорово.',
        ]);

        app(TurnOrderService::class)->rotateAfterCompletedCycle($cycle);
    }

    private function seedCycle3Ten(): void
    {
        if (ReadingCycle::where('club_id', $this->club->id)->where('cycle_number', 3)->exists()) {
            return;
        }

        $cycle = ReadingCycle::create([
            'club_id' => $this->club->id,
            'book_id' => $this->books['ten']->id,
            'proposer_id' => $this->members['ivan@chku.local']->id,
            'cycle_number' => 3,
            'status' => ReadingCycleStatusEnum::Active,
        ]);

        $meeting = Meeting::create([
            'reading_cycle_id' => $cycle->id,
            'title' => 'Обсуждение книги',
            'date' => '2026-05-30',
            'time' => '19:00:00',
            'place' => 'Онлайн',
            'is_online' => true,

        ]);

        MeetingRsvp::create([
            'meeting_id' => $meeting->id,
            'club_member_id' => $this->members['ivan@chku.local']->id,
            'status' => MeetingRsvpStatusEnum::Attending,
        ]);

        MeetingRsvp::create([
            'meeting_id' => $meeting->id,
            'club_member_id' => $this->members['nastya@chku.local']->id,
            'status' => MeetingRsvpStatusEnum::Attending,
        ]);

        MeetingRsvp::create([
            'meeting_id' => $meeting->id,
            'club_member_id' => $this->members['alexander@chku.local']->id,
            'status' => MeetingRsvpStatusEnum::Attending,
        ]);

        MeetingRsvp::create([
            'meeting_id' => $meeting->id,
            'club_member_id' => $this->members['denis@chku.local']->id,
            'status' => MeetingRsvpStatusEnum::Pending,
        ]);

        $progressData = [
            'andrey@chku.local' => ['status' => ReadingProgressStatusEnum::Finished, 'percent' => 100, 'finished_at' => '2026-05-11 00:00:00'],
            'alexander@chku.local' => ['status' => ReadingProgressStatusEnum::Finished, 'percent' => 100, 'finished_at' => '2026-05-11 00:00:00'],
            'nastya@chku.local' => ['status' => ReadingProgressStatusEnum::Reading, 'percent' => 75, 'finished_at' => null],
            'ivan@chku.local' => ['status' => ReadingProgressStatusEnum::Reading, 'percent' => 65, 'finished_at' => null],
            'denis@chku.local' => ['status' => ReadingProgressStatusEnum::Finished, 'percent' => 100, 'finished_at' => '2026-05-20 00:00:00'],
        ];

        foreach ($progressData as $email => $data) {
            ReadingProgress::create([
                'reading_cycle_id' => $cycle->id,
                'club_member_id' => $this->members[$email]->id,
                'status' => $data['status'],
                'progress_percent' => $data['percent'],
                'finished_at' => $data['finished_at'],
            ]);
        }
    }
}
