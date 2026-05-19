<?php

namespace Tests\Feature;

use App\Models\ClubMember;
use App\Models\TurnOrder;
use App\Models\User;
use App\Services\TurnOrderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            'joined_at' => '2024-01-15',
            'role' => 'member',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.avatarUrl', null);
        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'member_created']);
        $this->assertSame('new@example.com', collect($this->turnOrderEmails())->last());
    }

    public function test_admin_can_create_member_with_avatar(): void
    {
        Storage::fake('local');
        $this->actingAsAdmin();

        $response = $this->post('/api/members', [
            'name' => 'Новый Участник',
            'email' => 'new@example.com',
            'password' => 'secret123',
            'avatar' => UploadedFile::fake()->image('avatar.png', 300, 420),
            'joined_at' => '2024-01-15',
            'role' => 'member',
        ]);

        $response->assertCreated();
        $user = User::where('email', 'new@example.com')->firstOrFail();

        $response->assertJsonPath('data.avatarUrl', "/api/members/{$user->clubMember->id}/avatar");
        Storage::disk('local')->assertExists($user->avatar_path);
    }

    public function test_members_list_places_inactive_members_last(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/members');

        $response->assertOk();
        $members = collect($response->json('data'));

        $this->assertTrue($members->take(6)->every(fn (array $member) => $member['isActive'] === true));
        $this->assertSame(
            ['Игорь Фомин', 'Мария Лебедева'],
            $members->slice(6)->pluck('name')->all(),
        );
    }

    public function test_inactive_members_are_not_in_turn_order_after_seeding(): void
    {
        $this->actingAsAdmin();

        $emails = $this->turnOrderEmails();

        $this->assertCount(6, $emails);
        $this->assertNotContains('igor@example.com', $emails);
        $this->assertNotContains('maria@example.com', $emails);
    }

    public function test_member_cannot_create_member(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $response = $this->actingAs($user)->startSession()->postJson('/api/members', [
            'name' => 'Новый Участник',
            'email' => 'new@example.com',
            'password' => 'secret123',
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
        $this->assertNotContains('mikhail@example.com', $this->turnOrderEmails());
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

    private function turnOrderEmails(): array
    {
        $clubId = ClubMember::whereHas('user', fn ($query) => $query->where('email', 'elena@example.com'))
            ->value('club_id');

        return app(TurnOrderService::class)
            ->orderedTurnOrders($clubId)
            ->map(fn (TurnOrder $order) => $order->clubMember?->user?->email)
            ->all();
    }
}
