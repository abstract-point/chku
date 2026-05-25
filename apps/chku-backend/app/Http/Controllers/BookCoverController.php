<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookCoverResource;
use App\Models\Book;
use App\Services\BookCoverDownloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
