<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\NotificationDecision;
use App\Models\NotificationLog;

final class NotificationDecisionService
{
    public function decide(string $event, array $payload): NotificationDecision
    {
        $payloadHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        $recentDuplicateExists = NotificationLog::query()
            ->where('event', $event)
            ->where('channel', 'telegram')
            ->where('payload_hash', $payloadHash)
            ->whereIn('status', ['pending', 'sent'])
            ->where('created_at', '>=', now()->subMinutes(30))
            ->exists();

        if ($recentDuplicateExists) {
            return new NotificationDecision(false, 'recent_duplicate', $payloadHash);
        }

        return new NotificationDecision(true, null, $payloadHash);
    }
}
