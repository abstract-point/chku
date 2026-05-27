<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\ClubMember;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $club = Club::first();

        $membersData = [
            ['name' => 'Алексей Дмитриев', 'email' => 'admin@example.com', 'joined' => '2024-06-01', 'active' => true, 'genre' => 'science_fiction', 'role' => 'admin'],
            ['name' => 'Елена Воронцова', 'email' => 'elena@example.com', 'joined' => '2024-01-10', 'active' => true, 'genre' => 'classic', 'role' => 'admin'],
            ['name' => 'Михаил Корнев', 'email' => 'mikhail@example.com', 'joined' => '2024-02-15', 'active' => true, 'genre' => 'history', 'role' => 'member'],
            ['name' => 'Анна Соколова', 'email' => 'anna@example.com', 'joined' => '2024-03-01', 'active' => false, 'genre' => 'classic', 'role' => 'member'],
            ['name' => 'Павел Иванов', 'email' => 'pavel@example.com', 'joined' => '2024-04-10', 'active' => false, 'genre' => 'classic', 'role' => 'member'],
            ['name' => 'Марина Светлова', 'email' => 'marina@example.com', 'joined' => '2024-05-15', 'active' => false, 'genre' => 'history', 'role' => 'member'],
            ['name' => 'Игорь Фомин', 'email' => 'igor@example.com', 'joined' => '2023-06-01', 'active' => false, 'genre' => 'classic', 'role' => 'member'],
            ['name' => 'Мария Лебедева', 'email' => 'maria@example.com', 'joined' => '2023-08-15', 'active' => false, 'genre' => 'science_fiction', 'role' => 'member'],
        ];

        foreach ($membersData as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt('password'),
            ]);

            $member = ClubMember::create([
                'club_id' => $club->id,
                'user_id' => $user->id,
                'is_active' => $data['active'],
                'joined_at' => $data['joined'],
            ]);

            $genreId = Genre::where('slug', $data['genre'])->value('id');
            if ($genreId) {
                $member->favoriteGenres()->attach($genreId);
            }

            $user->assignRole($data['role']);
        }
    }
}
