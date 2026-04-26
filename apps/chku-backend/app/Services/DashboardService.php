<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\DashboardData;
use App\Models\BookCandidate;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\Meeting;
use App\Models\Rating;
use App\Models\ReadingCycle;
use App\Models\TurnOrder;

final class DashboardService
{
    public function __construct(private readonly CurrentMemberService $currentMember)
    {
    }

    public function getData(): DashboardData
    {
        $club = Club::with([
            'members' => function ($query) {
                $query->where('is_active', true);
            },
        ])->first();

        $currentCycle = ReadingCycle::with([
            'book.genre',
            'proposer.user',
            'readingProgress.clubMember.user',
        ])
            ->where('status', 'active')
            ->first();

        $nextMeeting = Meeting::with([
            'readingCycle',
            'rsvps.clubMember.user',
        ])
            ->whereHas('readingCycle', fn ($q) => $q->where('status', 'active'))
            ->first();

        $turnOrder = TurnOrder::with('clubMember.user')
            ->where('club_id', $club->id)
            ->orderBy('position')
            ->get();

        $activeCandidate = BookCandidate::with([
            'book.genre',
            'proposer.user',
            'responses.clubMember.user',
        ])
            ->where('status', 'pending')
            ->latest()
            ->first();

        return new DashboardData(
            club: $club,
            currentCycle: $currentCycle,
            currentUserProgress: $currentCycle?->readingProgress->firstWhere(
                'club_member_id',
                $this->currentMember->get()->id,
            ),
            memberProgress: $currentCycle?->readingProgress,
            nextMeeting: $nextMeeting,
            turnOrder: $turnOrder,
            activeCandidate: $activeCandidate,
            completedCyclesCount: ReadingCycle::where('status', 'completed')->count(),
            averageRating: (float) (Rating::avg('rating') ?? 0),
            activeMembersCount: ClubMember::where('is_active', true)->count(),
        );
    }
}
