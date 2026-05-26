<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\NotificationLog;
use App\Services\TelegramMessageFormatter;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class TelegramNotificationListener implements ShouldQueue
{
    public function __construct(
        private readonly TelegramService $telegram,
        private readonly TelegramMessageFormatter $formatter,
    ) {
    }

    public function handle(object $event): void
    {
        if (! $this->telegram->isEnabled()) {
            return;
        }

        $eventName = $this->formatter->eventName($event);
        $message = $this->formatter->format($event);

        $log = NotificationLog::logEvent($eventName, $message, $this->eventPayload($event));

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
