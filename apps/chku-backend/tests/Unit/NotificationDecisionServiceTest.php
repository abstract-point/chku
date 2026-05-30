<?php

namespace Tests\Unit;

use App\Models\NotificationLog;
use App\Services\NotificationDecisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationDecisionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_first_notification_for_payload(): void
    {
        $decision = app(NotificationDecisionService::class)->decide('meeting_scheduled', ['meeting_id' => 1]);

        $this->assertTrue($decision->shouldSend);
        $this->assertNull($decision->reason);
        $this->assertNotSame('', $decision->payloadHash);
    }

    public function test_skips_recent_duplicate_notification(): void
    {
        $payload = ['meeting_id' => 1];
        $service = app(NotificationDecisionService::class);
        $firstDecision = $service->decide('meeting_scheduled', $payload);

        NotificationLog::logEvent('meeting_scheduled', 'Message', $payload, $firstDecision->payloadHash)
            ->markSent();

        $secondDecision = $service->decide('meeting_scheduled', $payload);

        $this->assertFalse($secondDecision->shouldSend);
        $this->assertSame('recent_duplicate', $secondDecision->reason);
        $this->assertSame($firstDecision->payloadHash, $secondDecision->payloadHash);
    }
}
