<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $this->seed(TestDatabaseSeeder::class);
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
        $this->seed(TestDatabaseSeeder::class);

        $response = $this->startSession()->postJson('/api/login', [
            'email' => 'elena@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_authenticated_user_can_fetch_me(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertOk();
        $response->assertJsonPath('user.email', 'elena@example.com');
        $response->assertJsonPath('user.avatarUrl', null);
        $response->assertJsonPath('twoFactorEnabled', false);
        $response->assertJsonCount(1, 'user.favoriteGenres');
        $response->assertJsonPath('user.favoriteGenres.0.slug', 'classic');
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/me');
        $response->assertUnauthorized();
    }

    public function test_logout_returns_success(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $response = $this->actingAs($user)->startSession()->postJson('/api/logout');

        $response->assertOk();
        $response->assertJsonPath('message', 'Выход выполнен.');
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $genreId = \App\Models\Genre::where('slug', 'science_fiction')->value('id');

        $response = $this->actingAs($user)->patchJson('/api/me/profile', [
            'name' => 'Елена Новая',
            'email' => $user->email,
            'favorite_genre_ids' => [$genreId],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Елена Новая');
        $response->assertJsonPath('data.avatarUrl', null);
        $response->assertJsonCount(1, 'data.favoriteGenres');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Елена Новая']);
        $this->assertDatabaseHas('club_member_genre', [
            'club_member_id' => $user->clubMember?->id,
            'genre_id' => $genreId,
        ]);
    }

    public function test_authenticated_user_can_upload_avatar(): void
    {
        Storage::fake('local');
        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $response = $this->actingAs($user)->post('/api/me/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.png', 640, 480),
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.avatarUrl', "/storage/avatars/users/{$user->id}.jpg");

        $path = $user->refresh()->avatar_path;
        $this->assertSame("avatars/users/{$user->id}.jpg", $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame([256, 256], array_slice(getimagesize(Storage::disk('public')->path($path)), 0, 2));
    }

    public function test_authenticated_user_can_update_password(): void
    {
        $this->seed(TestDatabaseSeeder::class);
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
