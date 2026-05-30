<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['event', 'channel', 'status', 'message', 'payload', 'payload_hash', 'error', 'sent_at'])]
class NotificationLog extends Model
{
    use HasFactory;

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
    ];

    public static function logEvent(string $event, string $message, array $payload = [], ?string $payloadHash = null): self
    {
        return self::create([
            'event' => $event,
            'message' => $message,
            'payload' => $payload,
            'payload_hash' => $payloadHash,
            'status' => 'pending',
        ]);
    }

    public static function logSkipped(string $event, ?string $reason, array $payload = [], ?string $payloadHash = null): self
    {
        return self::create([
            'event' => $event,
            'message' => null,
            'payload' => $payload,
            'payload_hash' => $payloadHash,
            'status' => 'skipped',
            'error' => $reason,
        ]);
    }

    public function markSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error' => $error,
        ]);
    }
}
