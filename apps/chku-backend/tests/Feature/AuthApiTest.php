<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $response = $this->startSession()->postJson('/api/login', [
            'email' => 'elena@example.com',
            'password' => 'password',
        ]);

        $response->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->startSession()->postJson('/api/login', [
            'email' => 'elena@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_authenticated_user_can_fetch_me(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk();
        $response->assertJsonPath('user.email', 'elena@example.com');
        $response->assertJsonPath('twoFactorEnabled', false);
        $response->assertJsonPath('user.favoriteGenreId', $user->clubMember?->favorite_genre_id);
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/me');
        $response->assertUnauthorized();
    }

    public function test_logout_returns_success(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $response = $this->actingAs($user)->startSession()->postJson('/api/logout');

        $response->assertOk();
        $response->assertJsonPath('message', 'Выход выполнен.');
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $genreId = \App\Models\Genre::where('slug', 'scifi')->value('id');

        $response = $this->actingAs($user)->patchJson('/api/me/profile', [
            'name' => 'Елена Новая',
            'initials' => 'ЕН',
            'favorite_genre_id' => $genreId,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Елена Новая');
        $response->assertJsonPath('data.initials', 'ЕН');
        $response->assertJsonPath('data.favoriteGenreId', $genreId);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Елена Новая']);
        $this->assertDatabaseHas('club_members', [
            'user_id' => $user->id,
            'initials' => 'ЕН',
            'favorite_genre_id' => $genreId,
        ]);
    }

    public function test_authenticated_user_can_update_password(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $response = $this->actingAs($user)->putJson('/api/me/password', [
            'current_password' => 'password',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('new-secret-123', $user->refresh()->password));
    }
}
