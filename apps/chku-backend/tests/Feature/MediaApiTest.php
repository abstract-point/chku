<?php

namespace Tests\Feature;

use App\Http\Resources\BookCoverResource;
use App\Models\Book;
use App\Models\BookCover;
use App\Models\User;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_cover_resource_uses_api_media_urls(): void
    {
        $cover = $this->createStoredCover();

        $payload = (new BookCoverResource($cover))->resolve();

        $this->assertSame("/api/book-covers/{$cover->id}", $payload['url']);
        $this->assertSame("/api/book-covers/{$cover->id}/thumbnail", $payload['thumbnailUrl']);
    }

    public function test_authenticated_user_can_fetch_book_cover_images(): void
    {
        $cover = $this->createStoredCover();
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $this->actingAs($user)->get("/api/book-covers/{$cover->id}")
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $this->actingAs($user)->get("/api/book-covers/{$cover->id}/thumbnail")
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
    }

    public function test_book_cover_image_returns_not_found_when_file_is_missing(): void
    {
        $cover = $this->createStoredCover();
        Storage::disk('public')->delete($cover->cover_path);
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $this->actingAs($user)->get("/api/book-covers/{$cover->id}")
            ->assertNotFound();
    }

    private function createStoredCover(): BookCover
    {
        Storage::fake('public');
        $this->seed(TestDatabaseSeeder::class);

        $book = Book::query()->create([
            'title' => 'Тестовая книга',
            'author' => 'Тестовый автор',
            'slug' => 'test-book',
            'cover_color' => '#3a405a',
        ]);

        Storage::disk('public')->put('book-covers/test.jpg', $this->jpegBytes());
        Storage::disk('public')->put('book-covers/test-thumb.jpg', $this->jpegBytes());

        return $book->covers()->create([
            'cover_path' => 'book-covers/test.jpg',
            'thumbnail_path' => 'book-covers/test-thumb.jpg',
            'cover_width' => 1,
            'cover_height' => 1,
            'cover_size' => strlen($this->jpegBytes()),
        ]);
    }

    private function jpegBytes(): string
    {
        return base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAH/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAEFAqf/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/ASP/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/ASP/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAY/Al//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/IV//2gAMAwEAAgADAAAAEP/EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQMBAT8QH//EABQRAQAAAAAAAAAAAAAAAAAAABD/2gAIAQIBAT8QH//EABQQAQAAAAAAAAAAAAAAAAAAABD/2gAIAQEAAT8QH//Z');
    }
}
