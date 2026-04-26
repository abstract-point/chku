<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BookCandidateResponseEnum;
use App\Enums\BookCandidateStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Http\Resources\BookCandidateResource;
use App\Models\Book;
use App\Models\BookCandidate;
use App\Models\BookCandidateResponse;
use App\Models\ClubMember;
use App\Models\Genre;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use App\Services\CurrentMemberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class BookCandidateController extends Controller
{
    public function active(): BookCandidateResource|JsonResponse
    {
        $candidate = BookCandidate::with([
            'book.genre',
            'proposer.user',
            'responses.clubMember.user',
        ])
            ->where('status', 'pending')
            ->latest()
            ->first();

        return $candidate
            ? new BookCandidateResource($candidate)
            : response()->json(null);
    }

    public function store(Request $request, CurrentMemberService $currentMember): BookCandidateResource
    {
        $payload = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $candidate = DB::transaction(function () use ($payload, $currentMember) {
            $proposer = $currentMember->get();

            $book = Book::firstOrCreate(
                [
                    'slug' => $this->uniqueSlug($payload['title']),
                ],
                [
                    'title' => $payload['title'],
                    'author' => $payload['author'],
                    'description' => $payload['description'],
                    'genre_id' => Genre::where('slug', 'fiction')->value('id'),
                    'cover_color' => '#3a405a',
                ],
            );

            $candidate = BookCandidate::create([
                'book_id' => $book->id,
                'proposer_id' => $proposer->id,
                'reason' => $payload['reason'],
                'description' => $payload['description'],
                'status' => BookCandidateStatusEnum::Pending,
            ]);

            ClubMember::query()
                ->where('club_id', $proposer->club_id)
                ->where('is_active', true)
                ->get()
                ->each(fn (ClubMember $member) => BookCandidateResponse::create([
                    'book_candidate_id' => $candidate->id,
                    'club_member_id' => $member->id,
                    'response' => BookCandidateResponseEnum::Pending,
                ]));

            return $candidate;
        });

        return new BookCandidateResource($candidate->load([
            'book.genre',
            'proposer.user',
            'responses.clubMember.user',
        ]));
    }

    public function respond(
        Request $request,
        BookCandidate $candidate,
        CurrentMemberService $currentMember,
    ): BookCandidateResource {
        $payload = $request->validate([
            'response' => ['required', Rule::enum(BookCandidateResponseEnum::class)],
        ]);

        BookCandidateResponse::updateOrCreate(
            [
                'book_candidate_id' => $candidate->id,
                'club_member_id' => $currentMember->get()->id,
            ],
            [
                'response' => BookCandidateResponseEnum::from($payload['response']),
            ],
        );

        return new BookCandidateResource($candidate->refresh()->load([
            'book.genre',
            'proposer.user',
            'responses.clubMember.user',
        ]));
    }

    public function approve(BookCandidate $candidate): BookCandidateResource|JsonResponse
    {
        $candidate->load(['proposer', 'responses.clubMember']);

        $activeMemberIds = ClubMember::where('club_id', $candidate->proposer->club_id)
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        $activeResponses = $candidate->responses
            ->whereIn('club_member_id', $activeMemberIds)
            ->pluck('response', 'club_member_id');

        $canApprove = count($activeResponses) === count($activeMemberIds)
            && $activeResponses->every(fn (BookCandidateResponseEnum $response) => $response === BookCandidateResponseEnum::NotRead);

        if (! $canApprove) {
            return response()->json([
                'message' => 'Кандидата можно утвердить только когда все активные участники ответили not_read.',
            ], 422);
        }

        DB::transaction(function () use ($candidate) {
            $cycle = ReadingCycle::create([
                'club_id' => $candidate->proposer->club_id,
                'book_id' => $candidate->book_id,
                'proposer_id' => $candidate->proposer_id,
                'cycle_number' => (int) ReadingCycle::max('cycle_number') + 1,
                'status' => ReadingCycleStatusEnum::Proposed,
            ]);

            $candidate->update([
                'reading_cycle_id' => $cycle->id,
                'status' => BookCandidateStatusEnum::Approved,
            ]);

            ClubMember::query()
                ->where('club_id', $candidate->proposer->club_id)
                ->where('is_active', true)
                ->get()
                ->each(fn (ClubMember $member) => ReadingProgress::create([
                    'reading_cycle_id' => $cycle->id,
                    'club_member_id' => $member->id,
                    'status' => ReadingProgressStatusEnum::NotStarted,
                ]));
        });

        return new BookCandidateResource($candidate->refresh()->load([
            'book.genre',
            'proposer.user',
            'responses.clubMember.user',
        ]));
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'book';
        $slug = $base;
        $index = 2;

        while (Book::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$index}";
            $index++;
        }

        return $slug;
    }
}
