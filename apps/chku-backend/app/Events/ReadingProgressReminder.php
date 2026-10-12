<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ClubMember;
use App\Models\ReadingCycle;
use Illuminate\Support\Collection;

final class ReadingProgressReminder
{
    /**
     * @param  Collection<int, ClubMember>  $members  Members with a `progress_percent` attribute set.
     */
    public function __construct(
        public readonly ReadingCycle $cycle,
        public readonly Collection $members,
    ) {
    }
}
