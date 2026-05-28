<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\TelegramService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramServiceTest extends TestCase
{
    public function test_sends_message_with_configured_proxy(): void
    {
        Config::set('telegram.bot_token', 'test-token');
        Config::set('telegram.chat_id', '12345');
        Config::set('telegram.message_thread_id', null);
        Config::set('telegram.proxy', 'socks5h://127.0.0.1:1080');
        Config::set('telegram.timeout', 10);
        Config::set('telegram.retry_times', 1);
        Config::set('telegram.retry_sleep', 100);

        Http::fake([
            'https://api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 1],
            ]),
        ]);

        $result = app(TelegramService::class)->sendMessage('12345', 'Test message');

        $this->assertSame(['message_id' => 1], $result);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
            && $request['chat_id'] === '12345'
            && $request['text'] === 'Test message');
    }
}
