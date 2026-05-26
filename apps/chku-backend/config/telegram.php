<?php

return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'chat_id' => env('TELEGRAM_CHAT_ID'),
    'message_thread_id' => env('TELEGRAM_MESSAGE_THREAD_ID'),
    'enabled' => (bool) env('TELEGRAM_NOTIFICATIONS_ENABLED', false),
    'parse_mode' => env('TELEGRAM_PARSE_MODE', 'MarkdownV2'),
    'timeout' => (int) env('TELEGRAM_TIMEOUT', 10),
    'retry_times' => (int) env('TELEGRAM_RETRY_TIMES', 3),
    'retry_sleep' => (int) env('TELEGRAM_RETRY_SLEEP', 100),
];
