<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\NotificationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_event_creates_pending_record(): void
    {
        $log = NotificationLog::logEvent('test_event', 'Test message', ['key' => 'value']);

        $this->assertNotNull($log->id);
        $this->assertSame('test_event', $log->event);
        $this->assertSame('pending', $log->status);
        $this->assertSame('Test message', $log->message);
        $this->assertSame(['key' => 'value'], $log->payload);
        $this->assertNull($log->sent_at);
    }

    public function test_mark_sent_updates_status(): void
    {
        $log = NotificationLog::logEvent('test_event', 'Test message');
        $log->markSent();

        $this->assertSame('sent', $log->status);
        $this->assertNotNull($log->sent_at);
    }

    public function test_mark_failed_updates_status_with_error(): void
    {
        $log = NotificationLog::logEvent('test_event', 'Test message');
        $log->markFailed('Connection timeout');

        $this->assertSame('failed', $log->status);
        $this->assertSame('Connection timeout', $log->error);
    }

    public function test_notification_logs_are_persisted(): void
    {
        $log = NotificationLog::logEvent('event_a', 'Message A');
        $log->markSent();
        $log = $log->refresh();

        $this->assertDatabaseHas('notification_logs', [
            'id' => $log->id,
            'event' => 'event_a',
            'status' => 'sent',
            'message' => 'Message A',
        ]);
    }
}
