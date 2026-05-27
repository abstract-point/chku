<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Genre;
use Illuminate\Database\Seeder;

class ClubAndGenreSeeder extends Seeder
{
    public function run(): void
    {
        $club = Club::create([
            'name' => 'Читальный клуб умничек',
            'short_name' => 'ЧКУ',
        ]);

        $genres = [
            ['slug' => 'classic', 'name' => 'Классика'],
            ['slug' => 'contemporary_fiction', 'name' => 'Современная проза'],
            ['slug' => 'literary_fiction', 'name' => 'Литературная проза'],
            ['slug' => 'historical_fiction', 'name' => 'Историческая проза'],
            ['slug' => 'detective', 'name' => 'Детектив'],
            ['slug' => 'thriller', 'name' => 'Триллер'],
            ['slug' => 'horror', 'name' => 'Ужасы'],
            ['slug' => 'fantasy', 'name' => 'Фэнтези'],
            ['slug' => 'science_fiction', 'name' => 'Научная фантастика'],
            ['slug' => 'dystopia', 'name' => 'Антиутопия'],
            ['slug' => 'magical_realism', 'name' => 'Магический реализм'],
            ['slug' => 'adventure', 'name' => 'Приключения'],
            ['slug' => 'satire', 'name' => 'Сатира'],
            ['slug' => 'humor', 'name' => 'Юмор'],
            ['slug' => 'romance', 'name' => 'Романтика'],
            ['slug' => 'drama', 'name' => 'Драма'],
            ['slug' => 'family_saga', 'name' => 'Семейная сага'],
            ['slug' => 'coming_of_age', 'name' => 'Роман взросления'],
            ['slug' => 'war_fiction', 'name' => 'Военная проза'],
            ['slug' => 'crime', 'name' => 'Криминал'],
            ['slug' => 'spy_fiction', 'name' => 'Шпионская проза'],
            ['slug' => 'poetry', 'name' => 'Поэзия'],
            ['slug' => 'play', 'name' => 'Драматургия'],
            ['slug' => 'short_stories', 'name' => 'Рассказы'],
            ['slug' => 'graphic_novel', 'name' => 'Графический роман'],
            ['slug' => 'nonfiction', 'name' => 'Нон-фикшн'],
            ['slug' => 'biography', 'name' => 'Биография'],
            ['slug' => 'memoir', 'name' => 'Мемуары'],
            ['slug' => 'history', 'name' => 'История'],
            ['slug' => 'popular_science', 'name' => 'Научпоп'],
            ['slug' => 'philosophy', 'name' => 'Философия'],
            ['slug' => 'psychology', 'name' => 'Психология'],
            ['slug' => 'sociology', 'name' => 'Социология'],
            ['slug' => 'politics', 'name' => 'Политика'],
            ['slug' => 'economics', 'name' => 'Экономика'],
            ['slug' => 'business', 'name' => 'Бизнес'],
            ['slug' => 'self_development', 'name' => 'Саморазвитие'],
            ['slug' => 'travel', 'name' => 'Путешествия'],
            ['slug' => 'religion', 'name' => 'Религия'],
            ['slug' => 'art', 'name' => 'Искусство'],
            ['slug' => 'technology', 'name' => 'Технологии'],
            ['slug' => 'programming', 'name' => 'Программирование'],
            ['slug' => 'essays', 'name' => 'Эссе'],
            ['slug' => 'journalism', 'name' => 'Журналистика'],
            ['slug' => 'true_crime', 'name' => 'Документальный криминал'],
        ];

        foreach ($genres as $genreData) {
            Genre::create($genreData);
        }
    }
}
