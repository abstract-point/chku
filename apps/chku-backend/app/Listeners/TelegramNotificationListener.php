<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\NotificationLog;
use App\Services\NotificationDecisionService;
use App\Services\TelegramMessageFormatter;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class TelegramNotificationListener implements ShouldQueue
{
    public function __construct(
        private readonly TelegramService $telegram,
        private readonly TelegramMessageFormatter $formatter,
        private readonly NotificationDecisionService $decisions,
    ) {
    }

    public function handle(object $event): void
    {
        $eventName = $this->formatter->eventName($event);
        $payload = $this->eventPayload($event);

        if (! $this->telegram->isEnabled()) {
            NotificationLog::logSkipped($eventName, 'telegram_disabled', $payload);

            return;
        }

        $decision = $this->decisions->decide($eventName, $payload);

        if (! $decision->shouldSend) {
            NotificationLog::logSkipped($eventName, $decision->reason, $payload, $decision->payloadHash);

            return;
        }

        $message = $this->formatter->format($event);

        $log = NotificationLog::logEvent($eventName, $message, $payload, $decision->payloadHash);

        try {
            $result = $this->telegram->sendMessage(
                $this->telegram->getChatId(),
                $message,
            );

            if ($result !== null) {
                $log->markSent();
            } else {
                $log->markFailed('Telegram API returned null or error.');
                Log::warning('Telegram notification failed', [
                    'event' => $eventName,
                ]);
            }
        } catch (\Throwable $e) {
            $log->markFailed($e->getMessage());
            Log::error('Telegram notification exception', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function eventPayload(object $event): array
    {
        try {
            return json_decode(json_encode($event, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }
    }
}
