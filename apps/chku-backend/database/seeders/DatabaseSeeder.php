<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(ClubAndGenreSeeder::class);
        $this->call(ChatHistorySeeder::class);
        $this->call(BookSeeder::class);
    }
}
