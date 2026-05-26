<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class TelegramService
{
    private const API_BASE = 'https://api.telegram.org/bot';

    public function sendMessage(string $chatId, string $text, string $parseMode = 'MarkdownV2'): ?array
    {
        $token = config('telegram.bot_token');

        if (empty($token)) {
            throw new RuntimeException('TELEGRAM_BOT_TOKEN is not configured.');
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => true,
        ];

        $threadId = config('telegram.message_thread_id');
        if ($threadId !== null && $threadId !== '') {
            $payload['message_thread_id'] = (int) $threadId;
        }

        $response = Http::timeout(config('telegram.timeout', 10))
            ->retry(
                config('telegram.retry_times', 3),
                config('telegram.retry_sleep', 100),
            )
            ->post(self::API_BASE . $token . '/sendMessage', $payload);

        if (! $response->successful()) {
            Log::error('Telegram API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $data = $response->json();

        if (! ($data['ok'] ?? false)) {
            Log::error('Telegram API returned not ok', [
                'description' => $data['description'] ?? 'Unknown error',
            ]);

            return null;
        }

        return $data['result'] ?? null;
    }

    public function isEnabled(): bool
    {
        return config('telegram.enabled', false)
            && ! empty(config('telegram.bot_token'))
            && ! empty(config('telegram.chat_id'));
    }

    public function getChatId(): string
    {
        $chatId = config('telegram.chat_id');

        if (empty($chatId)) {
            throw new RuntimeException('TELEGRAM_CHAT_ID is not configured.');
        }

        return (string) $chatId;
    }
}
