<?php

namespace App\Http\Controllers;

use App\Enums\ReadingCycleStatusEnum;
use App\Http\Resources\CycleResource;
use App\Http\Resources\GenreResource;
use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingCycle;
use App\Services\BookCoverDownloadService;
use App\Services\CurrentMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class CycleController extends Controller
{
    public function __construct(
        private BookCoverDownloadService $coverDownloadService,
    ) {}

    public function index(CurrentMemberService $currentMember): AnonymousResourceCollection
    {
        $member = $currentMember->get();

        return CycleResource::collection(
            $this->baseQuery($member->club_id)
                ->orderByRaw("CASE WHEN status IN ('proposed', 'active') THEN 0 ELSE 1 END")
                ->orderByDesc('cycle_number')
                ->get()
        );
    }

    public function genres(CurrentMemberService $currentMember): AnonymousResourceCollection
    {
        $member = $currentMember->get();

        return GenreResource::collection(
            Genre::query()
                ->whereHas('books.readingCycles', function ($q) use ($member): void {
                    $q->where('club_id', $member->club_id)
                        ->where('status', ReadingCycleStatusEnum::Completed);
                })
                ->orderBy('name')
                ->get()
        );
    }

    public function show(CurrentMemberService $currentMember, int $cycleNumber): CycleResource
    {
        $member = $currentMember->get();

        return new CycleResource(
            $this->baseQuery($member->club_id)
                ->where('cycle_number', $cycleNumber)
                ->firstOrFail()
        );
    }

    public function updateBook(Request $request, CurrentMemberService $currentMember, int $cycleNumber): CycleResource
    {
        $member = $currentMember->get();
        $cycle = $this->baseQuery($member->club_id)
            ->where('cycle_number', $cycleNumber)
            ->firstOrFail();

        abort_unless($this->canEditBook($request, $cycle), 403);

        $isAdmin = $request->user()?->hasAnyRole(['admin', 'developer']) ?? false;
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'genre_ids' => ['nullable', 'array', 'max:5'],
            'genre_ids.*' => ['integer', Rule::exists('genres', 'id')],
            'coverFile' => ['nullable', 'image', 'max:5120'],
        ];

        if ($isAdmin) {
            $rules += [
                'coverColor' => ['nullable', 'string', 'max:32'],
            ];
        }

        $payload = $request->validate($rules);

        $book = $cycle->book;
        $book->update($this->bookPayload($payload, $book, $isAdmin));

        if (array_key_exists('genre_ids', $payload)) {
            $book->genres()->sync($payload['genre_ids'] ?? []);
        }

        $this->attachCover($book, $payload);

        return new CycleResource(
            $this->baseQuery($member->club_id)
                ->where('cycle_number', $cycleNumber)
                ->firstOrFail()
        );
    }

    private function baseQuery(int $clubId)
    {
        return ReadingCycle::with([
            'book.genres',
            'book.primaryCover',
            'proposer.user',
            'meeting',
            'meeting.rsvps',
            'bookCandidate.book.genres',
            'bookCandidate.proposer.user',
            'bookCandidate.responses.clubMember.user',
            'readingProgress.clubMember.user',
            'reviews.clubMember.user',
            'discussionMessages' => function ($q): void {
                $q->root()->with('replies.clubMember.user', 'clubMember.user');
            },
            'ratings',
        ])->where('club_id', $clubId);
    }

    private function canEditBook(Request $request, ReadingCycle $cycle): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'developer'])) {
            return true;
        }

        return $cycle->status !== ReadingCycleStatusEnum::Completed
            && $user->clubMember?->id === $cycle->proposer_id;
    }

    private function bookPayload(array $payload, Book $book, bool $isAdmin): array
    {
        $data = [
            'title' => $payload['title'],
            'author' => $payload['author'],
            'description' => $payload['description'] ?? null,
            'slug' => $this->uniqueSlug($payload['title'], $book->id),
        ];

        if ($isAdmin) {
            $data += [
                'cover_color' => $payload['coverColor'] ?? $book->cover_color,
            ];
        }

        return $data;
    }

    private function attachCover(Book $book, array $payload): void
    {
        if (! empty($payload['coverFile'])) {
            $this->coverDownloadService->storeUploaded($book, $payload['coverFile']);
        }
    }

    private function uniqueSlug(string $title, int $bookId): string
    {
        $base = Str::slug($title) ?: 'book';
        $slug = $base;
        $index = 2;

        while (Book::where('slug', $slug)->whereKeyNot($bookId)->exists()) {
            $slug = "{$base}-{$index}";
            $index++;
        }

        return $slug;
    }
}
