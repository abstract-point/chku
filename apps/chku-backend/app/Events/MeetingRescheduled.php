<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Meeting;

final class MeetingRescheduled
{
    public function __construct(
        public readonly Meeting $meeting,
        public readonly ?string $oldDate = null,
        public readonly ?string $oldTime = null,
        public readonly ?string $newDate = null,
        public readonly ?string $newTime = null,
    ) {
    }
}
