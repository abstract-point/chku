<?php

namespace App\Http\Controllers;

use App\Services\BookCoverSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BookLookupController extends Controller
{
    public function __construct(
        private BookCoverSearchService $searchService,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'title' => ['required_without:isbn', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:20'],
        ]);

        $title = trim($payload['title'] ?? '');
        $author = isset($payload['author']) ? trim($payload['author']) : null;
        $isbn = isset($payload['isbn']) ? trim($payload['isbn']) : null;

        if ($isbn === '' && ($title === '' || mb_strlen($title) < 2)) {
            return response()->json(['data' => []]);
        }

        $results = $this->searchService->search($title, $author, $isbn);

        return response()->json(['data' => $results]);
    }
}
