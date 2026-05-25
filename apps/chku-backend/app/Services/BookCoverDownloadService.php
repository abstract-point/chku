<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookCover;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class BookCoverDownloadService
{
    public function __construct(
        private BookCoverImageService $imageService,
    ) {}

    public function storeUploaded(Book $book, UploadedFile $file): ?BookCover
    {
        return $this->processAndStore($book, $file->getRealPath());
    }

    private function processAndStore(Book $book, string $sourcePath): ?BookCover
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
            'cover_width' => $processed['full']['width'],
            'cover_height' => $processed['full']['height'],
            'cover_size' => $processed['full']['size'],
        ]);
    }
}
