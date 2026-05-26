<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ReadingCycle;

final class CycleCompleted
{
    public function __construct(
        public readonly ReadingCycle $cycle,
    ) {
    }
}
