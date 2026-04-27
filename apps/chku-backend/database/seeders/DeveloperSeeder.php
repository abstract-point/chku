<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\ClubMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class DeveloperSeeder extends Seeder
{
    public function run(): void
    {
        $name = env('CHKU_DEVELOPER_NAME');
        $email = env('CHKU_DEVELOPER_EMAIL');
        $password = env('CHKU_DEVELOPER_PASSWORD');
        $initials = env('CHKU_DEVELOPER_INITIALS', 'DEV');

        if (! $name || ! $email || ! $password) {
            $this->command->warn('Developer credentials not set in .env. Skipping developer seed.');
            return;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => bcrypt($password),
            ]
        );

        $club = Club::first();
        if (! $club) {
            $this->command->error('No club found. Seed the club first.');
            return;
        }

        $member = ClubMember::firstOrCreate(
            ['user_id' => $user->id],
            [
                'club_id' => $club->id,
                'initials' => $initials,
                'is_active' => true,
                'joined_at' => now(),
            ]
        );

        $user->assignRole('developer');

        $this->command->info("Developer user created: {$email}");
    }
}
