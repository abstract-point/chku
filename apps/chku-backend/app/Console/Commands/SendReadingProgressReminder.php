<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

final class SendReadingProgressReminder extends Command
{
    protected $signature = 'reminders:reading-progress';

    protected $description = 'Send a Telegram reminder to members whose reading progress has not changed for a week';

    public function handle(ReminderService $reminders): int
    {
        $sent = $reminders->sendStaleProgressReminder();

        $this->info($sent ? 'Reading progress reminder sent.' : 'Nothing to remind about.');

        return self::SUCCESS;
    }
}
