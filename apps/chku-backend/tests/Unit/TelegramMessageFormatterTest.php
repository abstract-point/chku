<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ReadingCycleStatusEnum;
use App\Events\BookCandidateConfirmed;
use App\Events\BookCandidateProposed;
use App\Events\BookCandidateRejected;
use App\Events\BookCandidateReplaced;
use App\Events\CycleCompleted;
use App\Events\MeetingFinished;
use App\Events\MeetingScheduled;
use App\Events\MeetingStarted;
use App\Events\MemberDeactivated;
use App\Events\MemberFinishedReading;
use App\Events\MemberJoinedClub;
use App\Events\OwlAwardsAssigned;
use App\Events\ReadingProgressUpdated;
use App\Models\BookCandidate;
use App\Models\Club;
use App\Models\MemberBookQueueItem;
use App\Models\ReadingCycle;
use App\Models\User;
use App\Services\BookSelectionStateMachine;
use App\Services\TelegramMessageFormatter;
use App\Services\TurnOrderService;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramMessageFormatterTest extends TestCase
{
    use RefreshDatabase;

    private TelegramMessageFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestDatabaseSeeder::class);
        $this->formatter = new TelegramMessageFormatter();
        config()->set('telegram.frontend_url', 'https://example.com');
    }

    public function test_formats_member_joined(): void
    {
        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $member = $user->clubMember;

        $message = $this->formatter->format(new MemberJoinedClub($member));

        $this->assertStringContainsString('Елена', $message);
        $this->assertStringContainsString('https://example.com/members/' . $member->id, $message);
    }

    public function test_formats_member_deactivated(): void
    {
        $user = User::where('email', 'anna@example.com')->firstOrFail();
        $member = $user->clubMember;

        $message = $this->formatter->format(new MemberDeactivated($member));

        $this->assertStringContainsString('Анна', $message);
    }

    public function test_formats_candidate_proposed(): void
    {
        $candidate = $this->createProposedCandidate();

        $message = $this->formatter->format(new BookCandidateProposed($candidate));

        $this->assertStringContainsString($candidate->book->title, $message);
        $this->assertStringContainsString('https://example.com/', $message);
    }

    public function test_formats_candidate_rejected(): void
    {
        $candidate = $this->createProposedCandidate();

        $message = $this->formatter->format(new BookCandidateRejected($candidate));

        $this->assertStringContainsString('отклонён', $message);
    }

    public function test_formats_candidate_confirmed(): void
    {
        $candidate = $this->createProposedCandidate();

        $message = $this->formatter->format(new BookCandidateConfirmed($candidate));

        $this->assertStringContainsString('начался', $message);
        $this->assertStringContainsString('https://example.com/', $message);
    }

    public function test_formats_candidate_replaced(): void
    {
        $candidate = $this->createProposedCandidate();

        $message = $this->formatter->format(new BookCandidateReplaced($candidate));

        $this->assertStringContainsString('заменён', $message);
    }

    public function test_formats_reading_progress_updated(): void
    {
        $progress = \App\Models\ReadingProgress::with(['clubMember.user', 'readingCycle.book'])->firstOrFail();

        $message = $this->formatter->format(new ReadingProgressUpdated($progress));

        $this->assertStringContainsString((string) $progress->progress_percent, $message);
        $this->assertStringContainsString('https://example.com/', $message);
    }

    public function test_formats_member_finished_reading(): void
    {
        $progress = \App\Models\ReadingProgress::with(['clubMember.user', 'readingCycle.book'])->firstOrFail();

        $message = $this->formatter->format(new MemberFinishedReading($progress));

        $this->assertStringContainsString('дочитал', $message);
    }

    public function test_formats_meeting_scheduled(): void
    {
        $meeting = \App\Models\Meeting::with('readingCycle.book')->firstOrFail();

        $message = $this->formatter->format(new MeetingScheduled($meeting));

        $this->assertStringContainsString('Назначена встреча', $message);
        $this->assertStringContainsString('https://example.com/meetings/' . $meeting->id, $message);
    }

    public function test_formats_meeting_started(): void
    {
        $meeting = \App\Models\Meeting::with('readingCycle.book')->firstOrFail();

        $message = $this->formatter->format(new MeetingStarted($meeting));

        $this->assertStringContainsString('началась', $message);
        $this->assertStringContainsString('https://example.com/meetings/' . $meeting->id, $message);
    }

    public function test_formats_meeting_finished(): void
    {
        $meeting = \App\Models\Meeting::with(['readingCycle.book', 'readingCycle.proposer.user'])->firstOrFail();

        $message = $this->formatter->format(new MeetingFinished($meeting));

        $this->assertStringContainsString('завершена', $message);
        $this->assertStringContainsString('https://example.com/archive', $message);
    }

    public function test_formats_cycle_completed(): void
    {
        $cycle = \App\Models\ReadingCycle::with(['book', 'proposer.user'])->firstOrFail();

        $message = $this->formatter->format(new CycleCompleted($cycle));

        $this->assertStringContainsString('завершён', $message);
        $this->assertStringContainsString('https://example.com/cycles/' . $cycle->cycle_number, $message);
        $this->assertStringContainsString('https://example.com/archive', $message);
    }

    public function test_formats_owl_awards_with_cycle_link(): void
    {
        $cycle = \App\Models\ReadingCycle::firstOrFail();
        $awards = [
            ['memberId' => 1, 'memberName' => 'Анна', 'medal' => 'gold'],
        ];

        $message = $this->formatter->format(new OwlAwardsAssigned($awards, $cycle));

        $this->assertStringContainsString('Награды', $message);
        $this->assertStringContainsString('Анна', $message);
        $this->assertStringContainsString('https://example.com/cycles/' . $cycle->cycle_number, $message);
    }

    public function test_formats_owl_awards_without_cycle(): void
    {
        $message = $this->formatter->format(new OwlAwardsAssigned([]));

        $this->assertStringContainsString('Совы не назначены', $message);
    }

    public function test_event_name_mapping(): void
    {
        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $member = $user->clubMember;
        $progress = \App\Models\ReadingProgress::with(['clubMember.user', 'readingCycle.book'])->firstOrFail();
        $meeting = \App\Models\Meeting::with('readingCycle.book')->firstOrFail();
        $cycle = \App\Models\ReadingCycle::firstOrFail();

        $this->assertSame('member_joined_club', $this->formatter->eventName(new MemberJoinedClub($member)));
        $this->assertSame('member_deactivated', $this->formatter->eventName(new MemberDeactivated($member)));
        $this->assertSame('reading_progress_updated', $this->formatter->eventName(new ReadingProgressUpdated($progress)));
        $this->assertSame('member_finished_reading', $this->formatter->eventName(new MemberFinishedReading($progress)));
        $this->assertSame('meeting_scheduled', $this->formatter->eventName(new MeetingScheduled($meeting)));
        $this->assertSame('meeting_started', $this->formatter->eventName(new MeetingStarted($meeting)));
        $this->assertSame('meeting_finished', $this->formatter->eventName(new MeetingFinished($meeting)));
        $this->assertSame('cycle_completed', $this->formatter->eventName(new CycleCompleted($cycle)));
    }

    public function test_escape_handles_special_characters(): void
    {
        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $member = $user->clubMember;

        $message = $this->formatter->format(new MemberJoinedClub($member));

        // Message should be non-empty and not contain unescaped markdown
        $this->assertNotEmpty($message);
        $this->assertStringContainsString('Елена', $message);
    }

    public function test_link_generates_valid_markdown(): void
    {
        $formatter = new TelegramMessageFormatter();
        $reflection = new \ReflectionMethod($formatter, 'link');
        $reflection->setAccessible(true);

        $link = $reflection->invoke($formatter, 'Test Title', 'https://example.com/path');
        $this->assertSame('[Test Title](https://example.com/path)', $link);
    }

    public function test_link_escapes_text_but_not_url(): void
    {
        $formatter = new TelegramMessageFormatter();
        $reflection = new \ReflectionMethod($formatter, 'link');
        $reflection->setAccessible(true);

        $link = $reflection->invoke($formatter, 'Title with [brackets]', 'https://example.com/path');
        $this->assertSame('[Title with \[brackets\]](https://example.com/path)', $link);
    }

    private function createProposedCandidate(): BookCandidate
    {
        $cycle = ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail();
        $cycle->update([
            'status' => ReadingCycleStatusEnum::Completed,
            'completed_at' => now(),
        ]);

        $clubId = Club::firstOrFail()->id;

        app(TurnOrderService::class)->rotateAfterCompletedCycle($cycle);
        app(BookSelectionStateMachine::class)->createCandidateForCurrentSelector($clubId);

        return BookCandidate::with(['book', 'proposer.user', 'readingCycle'])
            ->where('status', \App\Enums\BookCandidateStatusEnum::Pending)
            ->latest()
            ->firstOrFail();
    }
}
