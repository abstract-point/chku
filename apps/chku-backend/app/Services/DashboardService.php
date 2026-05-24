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

final class DashboardService
{
    public function __construct(
        private readonly CurrentMemberService $currentMember,
        private readonly TurnOrderService $turnOrderService,
    ) {
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
            'readingProgress' => function ($query) {
                $query->whereHas('clubMember', fn ($q) => $q->where('is_active', true));
            },
            'readingProgress.clubMember.user',
        ])
            ->where('status', 'active')
            ->first();

        $nextMeeting = Meeting::with([
            'readingCycle',
            'rsvps.clubMember.user',
            'rsvps.clubMember.favoriteGenre',
        ])
            ->whereHas('readingCycle', fn ($q) => $q->where('status', 'active'))
            ->orderBy('date')
            ->first();

        $turnOrder = $this->turnOrderService->orderedTurnOrders($club->id);

        $activeCandidate = BookCandidate::with([
            'book.genre',
            'proposer.user',
            'readingCycle',
            'responses.clubMember.user',
        ])
            ->whereIn('status', ['pending', 'awaiting_owner_confirmation'])
            ->latest()
            ->first();

        $currentMember = $this->currentMember->get();
        $currentSelector = $this->turnOrderService->currentSelector($club->id);
        $upcomingSelector = $this->turnOrderService->nextSelector($club->id);
        $nextSelector = $currentCycle ? $upcomingSelector : $currentSelector;
        $nextSelectorQueueEmpty = $nextSelector
            ? ! $nextSelector->bookQueueItems()->where('status', 'queued')->exists()
            : false;

        $missingRatings = collect();
        if ($currentCycle && $nextMeeting) {
            $attendingMemberIds = $nextMeeting->rsvps()
                ->where('status', \App\Enums\MeetingRsvpStatusEnum::Attending)
                ->pluck('club_member_id');

            $ratedMemberIds = $currentCycle->ratings()->pluck('club_member_id');

            $missingRatings = ClubMember::with('user')
                ->where('club_id', $club->id)
                ->whereIn('id', $attendingMemberIds)
                ->whereNotIn('id', $ratedMemberIds)
                ->get();
        }

        return new DashboardData(
            club: $club,
            currentCycle: $currentCycle,
            currentUserProgress: $currentCycle?->readingProgress->firstWhere(
                'club_member_id',
                $currentMember->id,
            ),
            memberProgress: $currentCycle?->readingProgress,
            nextMeeting: $nextMeeting,
            turnOrder: $turnOrder,
            activeCandidate: $activeCandidate,
            currentMember: $currentMember,
            nextSelector: $nextSelector,
            nextSelectorQueueEmpty: $nextSelectorQueueEmpty,
            missingRatings: $missingRatings,
            completedCyclesCount: ReadingCycle::where('status', 'completed')->count(),
            averageRating: (float) (Rating::avg('rating') ?? 0),
            activeMembersCount: ClubMember::where('is_active', true)->count(),
        );
    }
}
