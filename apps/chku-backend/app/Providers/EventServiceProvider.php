<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\BookCandidateAwaitingConfirmation;
use App\Events\BookCandidateConfirmed;
use App\Events\BookCandidateProposed;
use App\Events\BookCandidateRejected;
use App\Events\BookCandidateReplaced;
use App\Events\CycleCompleted;
use App\Events\MeetingFinished;
use App\Events\MeetingRescheduled;
use App\Events\MeetingScheduled;
use App\Events\MeetingRatingReminder;
use App\Events\MeetingRsvpReminder;
use App\Events\MeetingStarted;
use App\Events\MemberDeactivated;
use App\Events\MemberFinishedReading;
use App\Events\MemberJoinedClub;
use App\Events\MemberResumed;
use App\Events\OwlAwardsAssigned;
use App\Events\ReadingProgressReminder;
use App\Events\ReadingProgressUpdated;
use App\Listeners\TelegramNotificationListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MemberJoinedClub::class => [
            TelegramNotificationListener::class,
        ],
        MemberResumed::class => [
            TelegramNotificationListener::class,
        ],
        MemberDeactivated::class => [
            TelegramNotificationListener::class,
        ],
        BookCandidateProposed::class => [
            TelegramNotificationListener::class,
        ],
        BookCandidateRejected::class => [
            TelegramNotificationListener::class,
        ],
        BookCandidateAwaitingConfirmation::class => [
            TelegramNotificationListener::class,
        ],
        BookCandidateConfirmed::class => [
            TelegramNotificationListener::class,
        ],
        BookCandidateReplaced::class => [
            TelegramNotificationListener::class,
        ],
        ReadingProgressUpdated::class => [
            TelegramNotificationListener::class,
        ],
        MemberFinishedReading::class => [
            TelegramNotificationListener::class,
        ],
        MeetingScheduled::class => [
            TelegramNotificationListener::class,
        ],
        MeetingRescheduled::class => [
            TelegramNotificationListener::class,
        ],
        MeetingStarted::class => [
            TelegramNotificationListener::class,
        ],
        MeetingFinished::class => [
            TelegramNotificationListener::class,
        ],
        CycleCompleted::class => [
            TelegramNotificationListener::class,
        ],
        OwlAwardsAssigned::class => [
            TelegramNotificationListener::class,
        ],
        MeetingRsvpReminder::class => [
            TelegramNotificationListener::class,
        ],
        MeetingRatingReminder::class => [
            TelegramNotificationListener::class,
        ],
        ReadingProgressReminder::class => [
            TelegramNotificationListener::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
