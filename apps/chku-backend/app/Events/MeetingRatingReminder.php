<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ClubMember;
use App\Models\Meeting;
use Illuminate\Support\Collection;

final class MeetingRatingReminder
{
    /**
     * @param  Collection<int, ClubMember>  $members
     */
    public function __construct(
        public readonly Meeting $meeting,
        public readonly Collection $members,
    ) {
    }
}
