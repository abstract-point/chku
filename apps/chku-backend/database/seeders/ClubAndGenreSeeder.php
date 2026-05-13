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
            ['slug' => 'fiction', 'name' => 'Проза'],
            ['slug' => 'nonfiction', 'name' => 'Нон-фикшн'],
            ['slug' => 'scifi', 'name' => 'Фантастика'],
        ];

        foreach ($genres as $genreData) {
            Genre::create($genreData);
        }
    }
}
