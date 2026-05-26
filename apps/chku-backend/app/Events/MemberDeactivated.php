<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ClubMember;

final class MemberDeactivated
{
    public function __construct(
        public readonly ClubMember $member,
    ) {
    }
}
