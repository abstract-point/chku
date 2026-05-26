<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\ClubMember;
use App\Models\Genre;
use App\Models\TurnOrder;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPermissions();
        $club = $this->seedClubAndGenres();
        $this->seedAdmin($club);
        $this->seedTurnOrder($club);
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

        $member = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
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

    private function seedClubAndGenres(): Club
    {
        $club = Club::firstOrCreate(
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

        return $club;
    }

    private function seedAdmin(Club $club): void
    {
        $email = env('CHKU_DEVELOPER_EMAIL');
        $password = env('CHKU_DEVELOPER_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('SEED_ADMIN_EMAIL or SEED_ADMIN_PASSWORD not set — skipping admin user creation.');

            return;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('CHKU_DEVELOPER_NAME', 'Администратор'),
                'password' => Hash::make($password),
            ],
        );

        if (! $user->wasRecentlyCreated) {
            $this->command?->info("Admin user {$email} already exists, skipping.");
        } else {
            $user->email_verified_at = now();
            $user->save();
            $this->command?->info("Admin user {$email} created.");
        }

        $user->assignRole('admin');

        ClubMember::firstOrCreate(
            ['club_id' => $club->id, 'user_id' => $user->id],
            [
                'is_active' => true,
                'joined_at' => now(),
            ],
        );
    }

    private function seedTurnOrder(Club $club): void
    {
        $existingCount = TurnOrder::where('club_id', $club->id)->count();
        if ($existingCount > 0) {
            $this->command?->info("{$existingCount} turn order entries already exist, skipping.");

            return;
        }

        $members = ClubMember::where('club_id', $club->id)
            ->where('is_active', true)
            ->get();

        if ($members->isEmpty()) {
            $this->command?->warn('No active members found, skipping turn order.');

            return;
        }

        $previous = null;

        foreach ($members as $member) {
            $order = TurnOrder::create([
                'club_id' => $club->id,
                'club_member_id' => $member->id,
                'next_turn_order_id' => null,
            ]);

            $previous?->update(['next_turn_order_id' => $order->id]);
            $previous = $order;
        }

        $this->command?->info("Seeded {$members->count()} turn order entries.");
    }
}
