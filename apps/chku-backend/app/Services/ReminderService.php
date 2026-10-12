<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MeetingRsvpStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Events\MeetingRatingReminder;
use App\Events\MeetingRsvpReminder;
use App\Events\ReadingProgressReminder;
use App\Models\ClubMember;
use App\Models\Meeting;
use App\Models\Rating;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use Carbon\Carbon;

final class ReminderService
{
    public const RSVP_REMINDER_HOURS_BEFORE = 24;

    public const RATING_REMINDER_MINUTES_AFTER_START = 15;

    public const STALE_PROGRESS_DAYS = 7;

    /**
     * A day before the meeting: remind members who have not answered whether they will attend.
     */
    public function sendRsvpReminders(): int
    {
        $sent = 0;

        $meetings = Meeting::query()
            ->whereNull('started_at')
            ->whereNull('finished_at')
            ->whereNull('rsvp_reminder_sent_at')
            ->whereHas('readingCycle', fn ($q) => $q->where('status', ReadingCycleStatusEnum::Active))
            ->with('readingCycle')
            ->get();

        foreach ($meetings as $meeting) {
            $startsAt = Carbon::parse("{$meeting->date->format('Y-m-d')} {$meeting->time}");
            if ($startsAt->isPast() || $startsAt->gt(now()->addHours(self::RSVP_REMINDER_HOURS_BEFORE))) {
                continue;
            }

            $answeredIds = $meeting->rsvps()
                ->where('status', '!=', MeetingRsvpStatusEnum::Pending->value)
                ->pluck('club_member_id');

            $members = ClubMember::with('user')
                ->where('club_id', $meeting->readingCycle->club_id)
                ->where('is_active', true)
                ->whereNotIn('id', $answeredIds)
                ->orderBy('id')
                ->get();

            $meeting->update(['rsvp_reminder_sent_at' => now()]);

            if ($members->isNotEmpty()) {
                event(new MeetingRsvpReminder($meeting, $members));
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Shortly after the meeting started: remind finished attendees who have not rated the book yet.
     */
    public function sendRatingReminders(): int
    {
        $sent = 0;

        $meetings = Meeting::query()
            ->whereNotNull('started_at')
            ->where('started_at', '<=', now()->subMinutes(self::RATING_REMINDER_MINUTES_AFTER_START))
            ->whereNull('finished_at')
            ->whereNull('rating_reminder_sent_at')
            ->get();

        foreach ($meetings as $meeting) {
            $requiredRaterIds = $meeting->requiredRaterIds();
            $ratedIds = Rating::query()
                ->where('reading_cycle_id', $meeting->reading_cycle_id)
                ->whereIn('club_member_id', $requiredRaterIds)
                ->pluck('club_member_id');

            $missingIds = $requiredRaterIds->diff($ratedIds);
            if ($missingIds->isEmpty()) {
                continue;
            }

            $members = ClubMember::with('user')->whereIn('id', $missingIds)->orderBy('id')->get();

            $meeting->update(['rating_reminder_sent_at' => now()]);
            event(new MeetingRatingReminder($meeting, $members));
            $sent++;
        }

        return $sent;
    }

    /**
     * Weekly: remind members still reading the current book whose progress has not changed for a week.
     */
    public function sendStaleProgressReminder(): bool
    {
        $cycle = ReadingCycle::query()
            ->where('status', ReadingCycleStatusEnum::Active)
            ->with('meeting')
            ->first();

        if (! $cycle || $cycle->meeting?->started_at !== null) {
            return false;
        }

        $staleBefore = now()->subDays(self::STALE_PROGRESS_DAYS);
        $progressByMember = ReadingProgress::query()
            ->where('reading_cycle_id', $cycle->id)
            ->get()
            ->keyBy('club_member_id');

        $members = ClubMember::with('user')
            ->where('club_id', $cycle->club_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->filter(function (ClubMember $member) use ($progressByMember, $cycle, $staleBefore): bool {
                $progress = $progressByMember->get($member->id);
                if ($progress === null) {
                    // No progress record yet: remind once the cycle has been active for a week.
                    return $cycle->updated_at?->lte($staleBefore) ?? false;
                }

                return in_array($progress->status, [ReadingProgressStatusEnum::NotStarted, ReadingProgressStatusEnum::Reading], true)
                    && $progress->updated_at->lte($staleBefore);
            })
            ->each(function (ClubMember $member) use ($progressByMember): void {
                $member->setAttribute('progress_percent', $progressByMember->get($member->id)?->progress_percent ?? 0);
            })
            ->values();

        if ($members->isEmpty()) {
            return false;
        }

        event(new ReadingProgressReminder($cycle, $members));

        return true;
    }
}
