<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\BookCandidate;
use App\Models\Club;
use App\Models\Meeting;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use Illuminate\Support\Collection;

final readonly class DashboardData
{
    public function __construct(
        public Club $club,
        public ?ReadingCycle $currentCycle,
        public ?ReadingProgress $currentUserProgress,
        public ?Collection $memberProgress,
        public ?Meeting $nextMeeting,
        public ?Collection $turnOrder,
        public ?BookCandidate $activeCandidate,
        public int $completedCyclesCount,
        public float $averageRating,
        public int $activeMembersCount,
    ) {
    }
}
