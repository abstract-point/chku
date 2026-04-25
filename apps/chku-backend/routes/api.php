<?php

use App\Http\Resources\ArchiveBookResource;
use App\Http\Resources\BookCandidateResource;
use App\Http\Resources\ClubResource;
use App\Http\Resources\DashboardResource;
use App\Http\Resources\MeetingResource;
use App\Http\Resources\MemberDetailResource;
use App\Http\Resources\MemberResource;
use App\Models\BookCandidate;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\Meeting;
use App\Models\Rating;
use App\Models\ReadingCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('ping'));
Route::get('ping', fn () => now())->name('ping');

Route::get('club', function () {
    return new ClubResource(Club::with('members.user')->first());
});

Route::get('members', function () {
    return MemberResource::collection(ClubMember::with('user', 'favoriteGenre')->get());
});

Route::get('members/{member}', function (ClubMember $member) {
    return new MemberDetailResource($member->load('user', 'favoriteGenre', 'readingProgress', 'proposedCycles', 'meetingRsvps'));
});

Route::get('dashboard', function () {
    $club = Club::with(['members' => function ($query) {
        $query->where('is_active', true);
    }])->first();

    $currentCycle = ReadingCycle::with(['book.genre', 'proposer.user', 'readingProgress.clubMember.user'])
        ->where('status', 'active')
        ->first();

    $nextMeeting = Meeting::with(['readingCycle', 'rsvps.clubMember.user'])
        ->whereHas('readingCycle', fn ($q) => $q->where('status', 'active'))
        ->first();

    $turnOrder = \App\Models\TurnOrder::with('clubMember.user')
        ->where('club_id', $club->id)
        ->orderBy('position')
        ->get();

    $activeCandidate = BookCandidate::with(['book.genre', 'proposer.user', 'responses.clubMember.user'])
        ->where('status', 'pending')
        ->latest()
        ->first();

    $dto = new \stdClass();
    $dto->club = $club;
    $dto->currentCycle = $currentCycle;
    $dto->currentUserProgress = $currentCycle?->readingProgress->first();
    $dto->memberProgress = $currentCycle?->readingProgress;
    $dto->nextMeeting = $nextMeeting;
    $dto->turnOrder = $turnOrder;
    $dto->activeCandidate = $activeCandidate;
    $dto->completedCyclesCount = ReadingCycle::where('status', 'completed')->count();
    $dto->averageRating = Rating::avg('rating') ?? 0;
    $dto->activeMembersCount = ClubMember::where('is_active', true)->count();

    return new DashboardResource($dto);
});

Route::get('archive', function () {
    return ArchiveBookResource::collection(
        ReadingCycle::with(['book.genre', 'proposer.user', 'meeting', 'reviews.clubMember.user', 'discussionMessages.clubMember.user', 'ratings'])
            ->where('status', 'completed')
            ->orderByDesc('cycle_number')
            ->get()
    );
});

Route::get('archive/{slug}', function (string $slug) {
    $cycle = ReadingCycle::with(['book.genre', 'proposer.user', 'meeting', 'reviews.clubMember.user', 'discussionMessages.clubMember.user', 'ratings'])
        ->whereHas('book', fn ($q) => $q->where('slug', $slug))
        ->where('status', 'completed')
        ->firstOrFail();

    return new ArchiveBookResource($cycle);
});

Route::get('meetings/{meeting}', function (Meeting $meeting) {
    return new MeetingResource($meeting->load('readingCycle.book', 'rsvps.clubMember.user'));
});

Route::get('candidates/active', function () {
    $candidate = BookCandidate::with(['book.genre', 'proposer.user', 'responses.clubMember.user'])
        ->where('status', 'pending')
        ->latest()
        ->first();

    return $candidate ? new BookCandidateResource($candidate) : response()->json(null);
});
