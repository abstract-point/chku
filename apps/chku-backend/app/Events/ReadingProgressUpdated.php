<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ReadingProgress;

final class ReadingProgressUpdated
{
    public function __construct(
        public readonly ReadingProgress $progress,
    ) {
    }
}
