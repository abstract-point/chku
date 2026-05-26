<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['event', 'channel', 'status', 'message', 'payload', 'error', 'sent_at'])]
class NotificationLog extends Model
{
    use HasFactory;

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
    ];

    public static function logEvent(string $event, string $message, array $payload = []): self
    {
        return self::create([
            'event' => $event,
            'message' => $message,
            'payload' => $payload,
            'status' => 'pending',
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
