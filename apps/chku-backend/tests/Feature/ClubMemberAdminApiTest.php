<?php

namespace Tests\Feature;

use App\Models\ClubMember;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubMemberAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(bool $withTwoFactor = true): self
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $user->assignRole('admin');

        if ($withTwoFactor) {
            $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        }

        return $this->actingAs($user)->startSession();
    }

    public function test_admin_can_create_member(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/members', [
            'name' => 'Новый Участник',
            'email' => 'new@example.com',
            'password' => 'secret123',
            'initials' => 'НУ',
            'joined_at' => '2024-01-15',
            'role' => 'member',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
        $this->assertDatabaseHas('club_members', ['initials' => 'НУ']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'member_created']);
    }

    public function test_member_cannot_create_member(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $response = $this->actingAs($user)->startSession()->postJson('/api/members', [
            'name' => 'Новый Участник',
            'email' => 'new@example.com',
            'password' => 'secret123',
            'initials' => 'НУ',
            'joined_at' => '2024-01-15',
            'role' => 'member',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_without_two_factor_cannot_create_member(): void
    {
        $this->actingAsAdmin(withTwoFactor: false);

        $response = $this->postJson('/api/members', [
            'name' => 'Новый Участник',
            'email' => 'new@example.com',
            'password' => 'secret123',
            'initials' => 'НУ',
            'joined_at' => '2024-01-15',
            'role' => 'member',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_deactivate_member(): void
    {
        $this->actingAsAdmin();
        $member = ClubMember::whereHas('user', fn ($q) => $q->where('email', 'mikhail@example.com'))->firstOrFail();

        $response = $this->postJson("/api/members/{$member->id}/deactivate");

        $response->assertOk();
        $this->assertDatabaseHas('club_members', [
            'id' => $member->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'member_deactivated']);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $this->actingAsAdmin();
        $member = ClubMember::whereHas('user', fn ($q) => $q->where('email', 'elena@example.com'))->firstOrFail();

        $response = $this->postJson("/api/members/{$member->id}/deactivate");

        $response->assertUnprocessable();
    }

    public function test_admin_without_two_factor_cannot_deactivate_member(): void
    {
        $this->actingAsAdmin(withTwoFactor: false);
        $member = ClubMember::whereHas('user', fn ($q) => $q->where('email', 'mikhail@example.com'))->firstOrFail();

        $response = $this->postJson("/api/members/{$member->id}/deactivate");

        $response->assertForbidden();
    }
}
