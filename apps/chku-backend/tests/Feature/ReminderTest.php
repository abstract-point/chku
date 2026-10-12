<?php

namespace Tests\Feature;

use App\Enums\ReadingProgressStatusEnum;
use App\Events\MeetingRatingReminder;
use App\Events\MeetingRsvpReminder;
use App\Events\ReadingProgressReminder;
use App\Models\ClubMember;
use App\Models\Meeting;
use App\Models\MeetingRsvp;
use App\Models\Rating;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use App\Models\User;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestDatabaseSeeder::class);
        Event::fake([MeetingRsvpReminder::class, MeetingRatingReminder::class, ReadingProgressReminder::class]);
    }

    public function test_rsvp_reminder_lists_members_who_did_not_answer_once(): void
    {
        $meeting = $this->meetingAt(now()->addHours(20));
        $meeting->rsvps()->delete();
        $answered = $this->member('elena@example.com');
        MeetingRsvp::create(['meeting_id' => $meeting->id, 'club_member_id' => $answered->id, 'status' => 'not_attending']);

        $this->artisan('reminders:meetings')->assertSuccessful();
        $this->artisan('reminders:meetings')->assertSuccessful();

        Event::assertDispatchedTimes(MeetingRsvpReminder::class, 1);
        Event::assertDispatched(MeetingRsvpReminder::class, function (MeetingRsvpReminder $event) use ($answered): bool {
            return $event->members->isNotEmpty()
                && ! $event->members->contains('id', $answered->id)
                && $event->members->every(fn (ClubMember $member) => $member->is_active);
        });
        $this->assertNotNull($meeting->refresh()->rsvp_reminder_sent_at);
    }

    public function test_rsvp_reminder_waits_until_day_before_meeting(): void
    {
        $this->meetingAt(now()->addDays(3));

        $this->artisan('reminders:meetings')->assertSuccessful();

        Event::assertNotDispatched(MeetingRsvpReminder::class);
    }

    public function test_rescheduling_meeting_rearms_rsvp_reminder(): void
    {
        $meeting = $this->meetingAt(now()->addHours(20));
        $meeting->update(['rsvp_reminder_sent_at' => now()]);

        $this->actingAs(User::where('email', 'elena@example.com')->firstOrFail())
            ->patchJson("/api/meetings/{$meeting->id}", ['date' => now()->addDays(5)->format('Y-m-d')])
            ->assertOk();

        $this->assertNull($meeting->refresh()->rsvp_reminder_sent_at);
    }

    public function test_rating_reminder_lists_finished_attendees_without_rating(): void
    {
        $meeting = $this->meetingAt(now()->subHour());
        $meeting->rsvps()->delete();
        $meeting->update(['started_at' => now()->subMinutes(20)]);
        $cycle = $meeting->readingCycle;

        [$rated, $unrated, $notFinished] = [
            $this->member('admin@example.com'),
            $this->member('elena@example.com'),
            $this->member('mikhail@example.com'),
        ];
        foreach ([$rated, $unrated, $notFinished] as $member) {
            MeetingRsvp::create(['meeting_id' => $meeting->id, 'club_member_id' => $member->id, 'status' => 'attending']);
        }
        $this->setProgress($cycle, $rated, 100);
        $this->setProgress($cycle, $unrated, 100);
        $this->setProgress($cycle, $notFinished, 50);
        Rating::create(['reading_cycle_id' => $cycle->id, 'club_member_id' => $rated->id, 'rating' => 8]);

        $this->artisan('reminders:meetings')->assertSuccessful();
        $this->artisan('reminders:meetings')->assertSuccessful();

        Event::assertDispatchedTimes(MeetingRatingReminder::class, 1);
        Event::assertDispatched(
            MeetingRatingReminder::class,
            fn (MeetingRatingReminder $event): bool => $event->members->pluck('id')->all() === [$unrated->id],
        );
    }

    public function test_rating_reminder_waits_fifteen_minutes_after_start(): void
    {
        $meeting = $this->meetingAt(now()->subHour());
        $meeting->update(['started_at' => now()->subMinutes(5)]);

        $this->artisan('reminders:meetings')->assertSuccessful();

        Event::assertNotDispatched(MeetingRatingReminder::class);
    }

    public function test_progress_reminder_lists_members_with_stale_progress(): void
    {
        $cycle = ReadingCycle::where('status', 'active')->firstOrFail();
        $cycle->meeting?->update(['started_at' => null]);
        [$stale, $fresh, $finished] = [
            $this->member('admin@example.com'),
            $this->member('elena@example.com'),
            $this->member('mikhail@example.com'),
        ];
        $this->setProgress($cycle, $stale, 30, now()->subDays(10));
        $this->setProgress($cycle, $fresh, 40, now()->subDay());
        $this->setProgress($cycle, $finished, 100, now()->subDays(10));

        $this->artisan('reminders:reading-progress')->assertSuccessful();

        Event::assertDispatched(
            ReadingProgressReminder::class,
            fn (ReadingProgressReminder $event): bool => $event->members->pluck('id')->all() === [$stale->id]
                && $event->members->first()->progress_percent === 30,
        );
    }

    public function test_progress_reminder_is_skipped_once_meeting_started(): void
    {
        $cycle = ReadingCycle::where('status', 'active')->firstOrFail();
        $cycle->meeting()->update(['started_at' => now()]);
        $this->setProgress($cycle, $this->member('admin@example.com'), 30, now()->subDays(10));

        $this->artisan('reminders:reading-progress')->assertSuccessful();

        Event::assertNotDispatched(ReadingProgressReminder::class);
    }

    private function meetingAt(\Carbon\CarbonInterface $at): Meeting
    {
        $meeting = ReadingCycle::where('status', 'active')->firstOrFail()->meeting()->firstOrFail();
        $meeting->update([
            'date' => $at->format('Y-m-d'),
            'time' => $at->format('H:i'),
            'started_at' => null,
            'finished_at' => null,
            'rsvp_reminder_sent_at' => null,
            'rating_reminder_sent_at' => null,
        ]);

        return $meeting->refresh();
    }

    private function member(string $email): ClubMember
    {
        return ClubMember::whereHas('user', fn ($query) => $query->where('email', $email))->firstOrFail();
    }

    private function setProgress(ReadingCycle $cycle, ClubMember $member, int $percent, ?\Carbon\CarbonInterface $updatedAt = null): void
    {
        $progress = ReadingProgress::updateOrCreate(
            ['reading_cycle_id' => $cycle->id, 'club_member_id' => $member->id],
            [
                'progress_percent' => $percent,
                'status' => $percent >= 100 ? ReadingProgressStatusEnum::Finished : ReadingProgressStatusEnum::Reading,
                'finished_at' => $percent >= 100 ? now() : null,
            ],
        );

        if ($updatedAt) {
            ReadingProgress::whereKey($progress->id)->update(['updated_at' => $updatedAt]);
        }
    }
}
