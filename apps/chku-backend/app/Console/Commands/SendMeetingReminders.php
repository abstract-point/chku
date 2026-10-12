<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

final class SendMeetingReminders extends Command
{
    protected $signature = 'reminders:meetings';

    protected $description = 'Send Telegram reminders about meeting RSVPs and missing ratings';

    public function handle(ReminderService $reminders): int
    {
        $rsvp = $reminders->sendRsvpReminders();
        $rating = $reminders->sendRatingReminders();

        $this->info("RSVP reminders: {$rsvp}, rating reminders: {$rating}.");

        return self::SUCCESS;
    }
}
