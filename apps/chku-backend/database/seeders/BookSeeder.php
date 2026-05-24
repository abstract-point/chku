<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $booksData = [
            ['title' => 'Мастер и Маргарита', 'author' => 'Михаил Булгаков', 'slug' => 'master-i-margarita', 'genre' => 'fiction', 'color' => '#3b2f2f', 'desc' => 'Роман о визите дьявола в Москву, о любви, творчестве и цене свободы.'],
            ['title' => 'Мы', 'author' => 'Евгений Замятин', 'slug' => 'my', 'genre' => 'scifi', 'color' => '#4a5568', 'desc' => 'Антиутопия о мире, где личность растворена в коллективе, а свобода мысли — преступление.'],
            ['title' => 'Sapiens. Краткая история человечества', 'author' => 'Юваль Ной Харари', 'slug' => 'sapiens', 'genre' => 'nonfiction', 'color' => '#8c5e58', 'desc' => 'Популярный обзор истории Homo sapiens от когнитивной революции до современных институтов.'],
            ['title' => 'Процесс', 'author' => 'Франц Кафка', 'slug' => 'process', 'genre' => 'fiction', 'color' => '#2d3748', 'desc' => 'История человека, которого судят за неизвестное преступление в абсурдной бюрократической системе.'],
            ['title' => 'Думай медленно... решай быстро', 'author' => 'Даниэль Канеман', 'slug' => 'dumai-medlenno-reshai-bystro', 'genre' => 'nonfiction', 'color' => '#6f7f4b', 'desc' => 'Исследование двух режимов мышления и когнитивных искажений.'],
            ['title' => 'Солярис', 'author' => 'Станислав Лем', 'slug' => 'solyaris', 'genre' => 'scifi', 'color' => '#46656b', 'desc' => 'Контакт с внеземным океаном оказывается разговором с собственными памятью и совестью.'],
            ['title' => 'Лавр', 'author' => 'Евгений Водолазкин', 'slug' => 'lavr', 'genre' => 'fiction', 'color' => '#7b5f4a', 'desc' => 'Роман о страннике Арсении, который проходит путь любви, вины и служения.'],
            ['title' => 'Дюна', 'author' => 'Фрэнк Герберт', 'slug' => 'duna', 'genre' => 'scifi', 'color' => '#4a5d4e', 'desc' => 'Политика, экология и религиозный миф на пустынной планете Арракис.', 'coverUrl' => 'https://covers.openlibrary.org/b/id/8108690-L.jpg'],
            ['title' => '1984', 'author' => 'Джордж Оруэлл', 'slug' => '1984', 'genre' => 'fiction', 'color' => '#2c363f', 'desc' => 'Антиутопия о языке, страхе и государственном контроле.'],
            ['title' => 'Тёмная башня', 'author' => 'Стивен Кинг', 'slug' => 'temnaya-bashnya', 'genre' => 'fiction', 'color' => '#5c3a21', 'desc' => 'Стрелок идёт через пустошь к Тёмной башне, которая связывает все миры.'],
            ['title' => 'Цветы для Элджернона', 'author' => 'Дэниел Киз', 'slug' => 'cvety-dlya-elzherona', 'genre' => 'scifi', 'color' => '#6b4c8a', 'desc' => 'История умственно отсталого человека, который обретает гениальность и теряет её.'],
            ['title' => 'Шум времени', 'author' => 'Джулиан Барнс', 'slug' => 'shum-vremeni', 'genre' => 'fiction', 'color' => '#4a4a5a', 'desc' => 'Роман о Дмитрии Шостаковиче, о компромиссе, страхе и достоинстве в эпоху террора.'],
            ['title' => 'Пикник на обочине', 'author' => 'Аркадий и Борис Стругацкие', 'slug' => 'piknik-na-obochine', 'genre' => 'scifi', 'color' => '#3d5a3d', 'desc' => 'Сталкеры проникают в Зону, оставленную пришельцами, где каждый шаг может стать последним.'],
            ['title' => 'Облачный атлас', 'author' => 'Дэвид Митчелл', 'slug' => 'oblachnyj-atlas', 'genre' => 'fiction', 'color' => '#5a6b7a', 'desc' => 'Шесть переплетённых историй из разных эпох о свободе, связи и последствиях.'],
            ['title' => 'Краткая история времени', 'author' => 'Стивен Хокинг', 'slug' => 'kratkaya-istoriya-vremeni', 'genre' => 'nonfiction', 'color' => '#2a3b4c', 'desc' => 'Доступное введение в космологию: чёрные дыры, Большой взрыв и природа времени.'],
        ];

        foreach ($booksData as $data) {
            Book::create([
                'title' => $data['title'],
                'author' => $data['author'],
                'slug' => $data['slug'],
                'genre_id' => Genre::where('slug', $data['genre'])->value('id'),
                'description' => $data['desc'],
                'cover_color' => $data['color'],
                'cover_url' => $data['coverUrl'] ?? null,
            ]);
        }
    }
}
