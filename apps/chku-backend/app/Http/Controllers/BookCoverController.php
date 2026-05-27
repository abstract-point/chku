<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookCoverResource;
use App\Models\Book;
use App\Models\BookCover;
use App\Services\BookCoverDownloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class BookCoverController extends Controller
{
    public function __construct(
        private BookCoverDownloadService $downloadService,
    ) {}

    public function store(Request $request, Book $book): BookCoverResource|JsonResponse
    {
        $payload = $request->validate([
            'cover' => ['required', 'image', 'max:5120'],
        ]);

        $cover = $this->downloadService->storeUploaded(
            $book,
            $payload['cover'],
        );

        if (! $cover) {
            return response()->json(['message' => 'Failed to process cover image.'], 422);
        }

        return new BookCoverResource($cover);
    }

    public function show(BookCover $cover): mixed
    {
        return $this->imageResponse($cover->cover_path);
    }

    public function thumbnail(BookCover $cover): mixed
    {
        return $this->imageResponse($cover->thumbnail_path);
    }

    private function imageResponse(string $path): mixed
    {
        if (! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
