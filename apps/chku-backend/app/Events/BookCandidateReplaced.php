<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\BookCandidate;

final class BookCandidateReplaced
{
    public function __construct(
        public readonly BookCandidate $candidate,
    ) {
    }
}
