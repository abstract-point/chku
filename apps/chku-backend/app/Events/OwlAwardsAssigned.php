<?php

declare(strict_types=1);

namespace App\Events;

final class OwlAwardsAssigned
{
    /**
     * @param array<int, array{memberId: int, memberName: string, medal: string}> $awards
     */
    public function __construct(
        public readonly array $awards,
    ) {
    }
}
