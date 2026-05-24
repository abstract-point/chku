<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookCover;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class BookCoverDownloadService
{
    public function __construct(
        private BookCoverImageService $imageService,
    ) {}

    public function downloadAndStore(Book $book, string $coverUrl, string $source): ?BookCover
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'cover_');

        try {
            $response = Http::timeout(10)->get($coverUrl);
            if (! $response->successful()) {
                return null;
            }

            file_put_contents($tempPath, $response->body());

            return $this->processAndStore($book, $tempPath, $source);
        } finally {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    public function storeUploaded(Book $book, UploadedFile $file, string $source): ?BookCover
    {
        return $this->processAndStore($book, $file->getRealPath(), $source);
    }

    private function processAndStore(Book $book, string $sourcePath, string $source): ?BookCover
    {
        try {
            $processed = $this->imageService->processFile($sourcePath);
        } catch (\RuntimeException) {
            return null;
        }

        $uuid = Str::uuid()->toString();
        $fullPath = "book-covers/{$uuid}.jpg";
        $thumbPath = "book-covers/{$uuid}-thumb.jpg";

        $disk = Storage::disk('public');
        $disk->put($fullPath, $processed['full']['content'], 'public');
        $disk->put($thumbPath, $processed['thumbnail']['content'], 'public');

        // Remove old covers
        $book->covers()->each(function (BookCover $cover) use ($disk) {
            $disk->delete($cover->cover_path);
            $disk->delete($cover->thumbnail_path);
            $cover->delete();
        });

        return $book->covers()->create([
            'cover_path' => $fullPath,
            'thumbnail_path' => $thumbPath,
            'cover_mime' => $processed['full']['mime'],
            'cover_width' => $processed['full']['width'],
            'cover_height' => $processed['full']['height'],
            'cover_size' => $processed['full']['size'],
            'cover_source' => $source,
        ]);
    }
}
