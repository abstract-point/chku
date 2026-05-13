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
            ['name' => 'Павел Иванов', 'initials' => 'П1', 'email' => 'pavel@example.com', 'joined' => '2024-01-10', 'active' => true, 'genre' => 'fiction', 'role' => 'member'],
            ['name' => 'Марина Светлова', 'initials' => 'П2', 'email' => 'marina@example.com', 'joined' => '2024-02-15', 'active' => true, 'genre' => 'nonfiction', 'role' => 'member'],
            ['name' => 'Алексей Дмитриев', 'initials' => 'АД', 'email' => 'admin@example.com', 'joined' => '2024-03-01', 'active' => true, 'genre' => 'scifi', 'role' => 'admin'],
            ['name' => 'Наталья Орлова', 'initials' => 'Н1', 'email' => 'natalya@example.com', 'joined' => '2023-06-01', 'active' => false, 'genre' => 'fiction', 'role' => 'member'],
            ['name' => 'Сергей Котов', 'initials' => 'Н2', 'email' => 'sergey@example.com', 'joined' => '2023-08-15', 'active' => false, 'genre' => 'scifi', 'role' => 'member'],
        ];

        foreach ($membersData as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt('password'),
            ]);

            ClubMember::create([
                'club_id' => $club->id,
                'user_id' => $user->id,
                'initials' => $data['initials'],
                'is_active' => $data['active'],
                'joined_at' => $data['joined'],
                'favorite_genre_id' => Genre::where('slug', $data['genre'])->value('id'),
            ]);

            $user->assignRole($data['role']);
        }
    }
}
