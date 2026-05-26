<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Meeting;

final class MeetingStarted
{
    public function __construct(
        public readonly Meeting $meeting,
    ) {
    }
}
