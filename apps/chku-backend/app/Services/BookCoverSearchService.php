<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class BookCoverSearchService
{
    private const GOOGLE_BOOKS_BASE = 'https://www.googleapis.com/books/v1/volumes';
    private const OPEN_LIBRARY_SEARCH = 'https://openlibrary.org/search.json';

    public function search(string $title, ?string $author = null, ?string $isbn = null): array
    {
        $results = collect();

        if ($isbn !== null && $isbn !== '') {
            $results = $results->merge($this->searchGoogleBooksByIsbn($isbn));
            $results = $results->merge($this->searchOpenLibraryByIsbn($isbn));
        }

        $normalizedTitle = $this->normalize($title);
        $normalizedAuthor = $author ? $this->normalize($author) : null;

        $results = $results->merge($this->searchGoogleBooks($normalizedTitle, $normalizedAuthor, 'ru'));
        $results = $results->merge($this->searchGoogleBooks($normalizedTitle, $normalizedAuthor, null));
        $results = $results->merge($this->searchGoogleBooks($normalizedTitle, null, null));
        $results = $results->merge($this->searchOpenLibrary($normalizedTitle, $normalizedAuthor));

        return $results
            ->unique(fn (array $cover) => $cover['coverUrl'])
            ->values()
            ->all();
    }

    private function searchGoogleBooksByIsbn(string $isbn): array
    {
        $query = ['q' => "isbn:{$isbn}"];
        if ($apiKey = config('services.google_books.api_key')) {
            $query['key'] = $apiKey;
        }

        $response = Http::acceptJson()->timeout(5)->get(self::GOOGLE_BOOKS_BASE, $query);

        if (! $response->successful()) {
            return [];
        }

        return collect($response->json('items', []))
            ->map(fn (array $item) => $this->normalizeGoogleBooksResult($item, 100))
            ->filter()
            ->all();
    }

    private function searchOpenLibraryByIsbn(string $isbn): array
    {
        $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbn);
        if ($cleanIsbn === '') {
            return [];
        }

        $coverUrl = "https://covers.openlibrary.org/b/isbn/{$cleanIsbn}-L.jpg?default=false";

        // Quick head check to see if cover exists
        $head = Http::timeout(5)->head($coverUrl);
        if (! $head->successful() || $head->header('Content-Type') === 'image/svg+xml') {
            return [];
        }

        return [[
            'source' => 'open_library',
            'title' => null,
            'authors' => [],
            'isbn' => $cleanIsbn,
            'coverUrl' => $coverUrl,
            'thumbnailUrl' => "https://covers.openlibrary.org/b/isbn/{$cleanIsbn}-M.jpg?default=false",
            'confidence' => 95,
        ]];
    }

    private function searchGoogleBooks(string $title, ?string $author, ?string $langRestrict): array
    {
        $q = $author
            ? "intitle:{$title}+inauthor:{$author}"
            : "intitle:{$title}";

        $query = ['q' => $q, 'maxResults' => 12];
        if ($langRestrict) {
            $query['langRestrict'] = $langRestrict;
        }
        if ($apiKey = config('services.google_books.api_key')) {
            $query['key'] = $apiKey;
        }

        $response = Http::acceptJson()->timeout(5)->get(self::GOOGLE_BOOKS_BASE, $query);

        if (! $response->successful()) {
            return [];
        }

        $confidence = $author && $langRestrict ? 90 : ($author ? 80 : 60);

        return collect($response->json('items', []))
            ->map(fn (array $item) => $this->normalizeGoogleBooksResult($item, $confidence))
            ->filter()
            ->all();
    }

    private function searchOpenLibrary(string $title, ?string $author): array
    {
        $params = [
            'title' => $title,
            'limit' => 12,
            'fields' => 'cover_i,title,author_name',
        ];

        if ($author) {
            $params['author'] = $author;
        }

        $response = Http::acceptJson()->timeout(5)->get(self::OPEN_LIBRARY_SEARCH, $params);

        if (! $response->successful()) {
            return [];
        }

        return collect($response->json('docs', []))
            ->map(fn (array $doc) => $this->normalizeOpenLibraryResult($doc))
            ->filter()
            ->all();
    }

    private function normalizeGoogleBooksResult(array $item, int $confidence): ?array
    {
        $volumeInfo = $item['volumeInfo'] ?? [];
        $imageLinks = $volumeInfo['imageLinks'] ?? [];

        $coverUrl = $imageLinks['thumbnail'] ?? $imageLinks['smallThumbnail'] ?? null;
        if (! $coverUrl) {
            return null;
        }

        // Upgrade zoom level for larger images
        $coverUrl = str_replace('zoom=1', 'zoom=0', $coverUrl);

        $isbn = null;
        foreach ($volumeInfo['industryIdentifiers'] ?? [] as $identifier) {
            if (in_array($identifier['type'] ?? '', ['ISBN_13', 'ISBN_10'], true)) {
                $isbn = $identifier['identifier'];
                break;
            }
        }

        return [
            'source' => 'google_books',
            'title' => $volumeInfo['title'] ?? null,
            'authors' => $volumeInfo['authors'] ?? [],
            'isbn' => $isbn,
            'coverUrl' => $coverUrl,
            'thumbnailUrl' => $imageLinks['smallThumbnail'] ?? $coverUrl,
            'confidence' => $confidence,
        ];
    }

    private function normalizeOpenLibraryResult(array $doc): ?array
    {
        $coverId = $doc['cover_i'] ?? null;
        if (! $coverId) {
            return null;
        }

        return [
            'source' => 'open_library',
            'title' => $doc['title'] ?? null,
            'authors' => $doc['author_name'] ?? [],
            'isbn' => null,
            'coverUrl' => "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg",
            'thumbnailUrl' => "https://covers.openlibrary.org/b/id/{$coverId}-M.jpg",
            'confidence' => 50,
        ];
    }

    private function normalize(string $input): string
    {
        $input = mb_strtolower($input, 'UTF-8');
        $input = preg_replace('/[«»""]/u', '', $input);
        $input = preg_replace('/\s+/u', ' ', $input);

        return trim($input);
    }
}
