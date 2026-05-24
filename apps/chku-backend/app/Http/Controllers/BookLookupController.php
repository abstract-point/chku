<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

final class BookLookupController extends Controller
{
    public function covers(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
        ]);

        $title = trim($payload['title']);
        $author = trim($payload['author']);

        if ($title === '' || $author === '' || mb_strlen($title.$author) < 3) {
            return response()->json(['data' => []]);
        }

        $response = Http::acceptJson()
            ->timeout(5)
            ->get('https://openlibrary.org/search.json', [
                'title' => $title,
                'author' => $author,
                'limit' => 12,
                'fields' => 'cover_i',
            ]);

        if (! $response->successful()) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => collect($response->json('docs', []))
                ->map(fn (array $doc) => $this->normalizeResult($doc))
                ->filter(fn (?array $doc) => $doc !== null)
                ->unique('coverId')
                ->values(),
        ]);
    }

    private function normalizeResult(array $doc): ?array
    {
        $coverId = $doc['cover_i'] ?? null;

        if (! $coverId) {
            return null;
        }

        return [
            'coverId' => (string) $coverId,
            'coverUrl' => "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg",
            'thumbnailUrl' => "https://covers.openlibrary.org/b/id/{$coverId}-M.jpg",
        ];
    }
}
