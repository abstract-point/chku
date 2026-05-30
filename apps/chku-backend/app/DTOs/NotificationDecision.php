<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class NotificationDecision
{
    public function __construct(
        public bool $shouldSend,
        public ?string $reason,
        public string $payloadHash,
    ) {
    }
}
