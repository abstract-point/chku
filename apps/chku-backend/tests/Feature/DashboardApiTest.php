<?php

namespace Tests\Feature;

use App\Enums\BookCandidateResponseEnum;
use App\Enums\BookCandidateStatusEnum;
use App\Enums\MeetingRsvpStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Models\Book;
use App\Models\BookCandidate;
use App\Models\BookCandidateResponse;
use App\Models\ClubMember;
use App\Models\Meeting;
use App\Models\MeetingRsvp;
use App\Models\ReadingCycle;
use App\Models\User;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_progress_includes_member_id_and_finished_at(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $cycle = ReadingCycle::where('status', 'active')->firstOrFail();
        $progress = $cycle->readingProgress()->firstOrFail();
        $finishedAt = now()->subMinutes(15)->milliseconds(0);

        $progress->update([
            'status' => ReadingProgressStatusEnum::Finished,
            'progress_percent' => 100,
            'finished_at' => $finishedAt,
        ]);

        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $response = $this->actingAs($user)->getJson('/api/dashboard');

        $response->assertOk();

        $item = collect($response->json('data.memberProgress'))
            ->firstWhere('id', $progress->club_member_id);

        $this->assertNotNull($item);
        $this->assertSame($progress->club_member_id, $item['id']);
        $this->assertNotNull($item['finishedAt']);
    }

    public function test_member_progress_medals_match_attending_owl_awards(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $cycle = ReadingCycle::where('status', 'active')->firstOrFail();
        $meeting = Meeting::where('reading_cycle_id', $cycle->id)->firstOrFail();
        $progress = $cycle->readingProgress()->with('clubMember.user')->get();

        $adminProgress = $progress->firstOrFail(fn ($item) => $item->clubMember->user->email === 'admin@example.com');
        $elenaProgress = $progress->firstOrFail(fn ($item) => $item->clubMember->user->email === 'elena@example.com');
        $mikhailProgress = $progress->firstOrFail(fn ($item) => $item->clubMember->user->email === 'mikhail@example.com');

        $adminProgress->update([
            'status' => ReadingProgressStatusEnum::Finished,
            'progress_percent' => 100,
            'finished_at' => now()->subMinutes(30),
        ]);
        $elenaProgress->update([
            'status' => ReadingProgressStatusEnum::Finished,
            'progress_percent' => 100,
            'finished_at' => now()->subMinutes(20),
        ]);
        $mikhailProgress->update([
            'status' => ReadingProgressStatusEnum::Finished,
            'progress_percent' => 100,
            'finished_at' => now()->subMinutes(10),
        ]);

        MeetingRsvp::updateOrCreate(
            ['meeting_id' => $meeting->id, 'club_member_id' => $adminProgress->club_member_id],
            ['status' => MeetingRsvpStatusEnum::Pending],
        );
        MeetingRsvp::updateOrCreate(
            ['meeting_id' => $meeting->id, 'club_member_id' => $elenaProgress->club_member_id],
            ['status' => MeetingRsvpStatusEnum::Attending],
        );
        MeetingRsvp::updateOrCreate(
            ['meeting_id' => $meeting->id, 'club_member_id' => $mikhailProgress->club_member_id],
            ['status' => MeetingRsvpStatusEnum::Attending],
        );

        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $response = $this->actingAs($user)->getJson('/api/dashboard');

        $response->assertOk();

        $items = collect($response->json('data.memberProgress'))->keyBy('id');

        $this->assertNull($items[$adminProgress->club_member_id]['medal']);
        $this->assertSame('gold', $items[$elenaProgress->club_member_id]['medal']);
        $this->assertSame('silver', $items[$mikhailProgress->club_member_id]['medal']);
    }

    public function test_member_progress_medals_break_finished_at_ties_by_member_id(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $cycle = ReadingCycle::where('status', 'active')->firstOrFail();
        $meeting = Meeting::where('reading_cycle_id', $cycle->id)->firstOrFail();
        $progress = $cycle->readingProgress()->with('clubMember.user')->get();

        $elenaProgress = $progress->firstOrFail(fn ($item) => $item->clubMember->user->email === 'elena@example.com');
        $mikhailProgress = $progress->firstOrFail(fn ($item) => $item->clubMember->user->email === 'mikhail@example.com');
        $finishedAt = now()->subMinutes(20)->milliseconds(0);

        foreach ([$elenaProgress, $mikhailProgress] as $item) {
            $item->update([
                'status' => ReadingProgressStatusEnum::Finished,
                'progress_percent' => 100,
                'finished_at' => $finishedAt,
            ]);

            MeetingRsvp::updateOrCreate(
                ['meeting_id' => $meeting->id, 'club_member_id' => $item->club_member_id],
                ['status' => MeetingRsvpStatusEnum::Attending],
            );
        }

        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $response = $this->actingAs($user)->getJson('/api/dashboard');

        $response->assertOk();

        $items = collect($response->json('data.memberProgress'))->keyBy('id');
        $first = collect([$elenaProgress, $mikhailProgress])->sortBy('club_member_id')->first();
        $second = collect([$elenaProgress, $mikhailProgress])->sortBy('club_member_id')->last();

        $this->assertSame('gold', $items[$first->club_member_id]['medal']);
        $this->assertSame('silver', $items[$second->club_member_id]['medal']);
    }

    public function test_turn_order_marks_current_cycle_proposer_and_next_selector(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $response = $this->actingAs($user)->getJson('/api/dashboard');

        $response->assertOk();
        $response->assertJsonPath('data.lifecycle.nextSelectorName', 'Елена Воронцова');

        $items = collect($response->json('data.turnOrder'))->keyBy('name');

        $this->assertTrue($items['Алексей Дмитриев']['isCurrentCycleProposer']);
        $this->assertFalse($items['Алексей Дмитриев']['isNextSelector']);
        $this->assertTrue($items['Елена Воронцова']['isNextSelector']);
        $this->assertFalse($items['Елена Воронцова']['isCurrentCycleProposer']);
    }

    public function test_next_action_prompts_member_to_answer_pending_candidate(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $member = $user->clubMember;
        $cycle = ReadingCycle::where('status', 'active')->firstOrFail();

        $candidate = BookCandidate::create([
            'book_id' => Book::where('slug', 'shum-vremeni')->firstOrFail()->id,
            'proposer_id' => ClubMember::whereHas('user', fn ($query) => $query->where('email', 'mikhail@example.com'))->firstOrFail()->id,
            'reading_cycle_id' => $cycle->id,
            'status' => BookCandidateStatusEnum::Pending,
        ]);

        BookCandidateResponse::create([
            'book_candidate_id' => $candidate->id,
            'club_member_id' => $member->id,
            'response' => BookCandidateResponseEnum::Pending,
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.nextAction.type', 'respond_candidate')
            ->assertJsonPath('data.nextAction.priority', 100)
            ->assertJsonPath('data.nextAction.actionUrl', '/');
    }

    public function test_next_action_prompts_attendee_to_rate_started_meeting(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $member = $user->clubMember;
        $cycle = ReadingCycle::where('status', 'active')->firstOrFail();
        $meeting = Meeting::where('reading_cycle_id', $cycle->id)->firstOrFail();
        $meeting->update(['started_at' => now()]);

        MeetingRsvp::updateOrCreate(
            ['meeting_id' => $meeting->id, 'club_member_id' => $member->id],
            ['status' => MeetingRsvpStatusEnum::Attending],
        );

        $response = $this->actingAs($user)->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.nextAction.type', 'rate_book')
            ->assertJsonPath('data.nextAction.actionUrl', "/meetings/{$meeting->id}");
    }

    public function test_next_action_prompts_member_to_rsvp_before_progress_update(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $meeting = Meeting::whereHas('readingCycle', fn ($query) => $query->where('status', 'active'))->firstOrFail();

        $response = $this->actingAs($user)->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.nextAction.type', 'rsvp_meeting')
            ->assertJsonPath('data.nextAction.actionUrl', "/meetings/{$meeting->id}");
    }

    public function test_next_action_prompts_member_to_update_missing_progress(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $member = $user->clubMember;
        $cycle = ReadingCycle::where('status', 'active')->firstOrFail();
        $meeting = Meeting::where('reading_cycle_id', $cycle->id)->firstOrFail();

        MeetingRsvp::updateOrCreate(
            ['meeting_id' => $meeting->id, 'club_member_id' => $member->id],
            ['status' => MeetingRsvpStatusEnum::NotAttending],
        );

        $cycle->readingProgress()
            ->where('club_member_id', $member->id)
            ->delete();

        $response = $this->actingAs($user)->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.nextAction.type', 'update_progress')
            ->assertJsonPath('data.nextAction.actionUrl', '/?action=update-progress#reading-progress');
    }

    public function test_next_action_prompts_member_to_update_stale_progress(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $member = $user->clubMember;
        $cycle = ReadingCycle::where('status', 'active')->firstOrFail();
        $meeting = Meeting::where('reading_cycle_id', $cycle->id)->firstOrFail();

        MeetingRsvp::updateOrCreate(
            ['meeting_id' => $meeting->id, 'club_member_id' => $member->id],
            ['status' => MeetingRsvpStatusEnum::NotAttending],
        );

        $cycle->readingProgress()
            ->where('club_member_id', $member->id)
            ->firstOrFail()
            ->forceFill(['updated_at' => now()->subDays(8)])
            ->save();

        $response = $this->actingAs($user)->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.nextAction.type', 'update_progress')
            ->assertJsonPath('data.nextAction.actionUrl', '/?action=update-progress#reading-progress');
    }

    public function test_next_action_skips_recent_progress_update(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $member = $user->clubMember;
        $cycle = ReadingCycle::where('status', 'active')->firstOrFail();
        $meeting = Meeting::where('reading_cycle_id', $cycle->id)->firstOrFail();

        MeetingRsvp::updateOrCreate(
            ['meeting_id' => $meeting->id, 'club_member_id' => $member->id],
            ['status' => MeetingRsvpStatusEnum::NotAttending],
        );

        $cycle->readingProgress()
            ->where('club_member_id', $member->id)
            ->firstOrFail()
            ->forceFill(['updated_at' => now()->subDays(3)])
            ->save();

        $response = $this->actingAs($user)->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.nextAction.type', 'none');
    }

    public function test_next_action_skips_finished_progress(): void
    {
        $this->seed(TestDatabaseSeeder::class);

        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $member = $user->clubMember;
        $cycle = ReadingCycle::where('status', 'active')->firstOrFail();
        $meeting = Meeting::where('reading_cycle_id', $cycle->id)->firstOrFail();

        MeetingRsvp::updateOrCreate(
            ['meeting_id' => $meeting->id, 'club_member_id' => $member->id],
            ['status' => MeetingRsvpStatusEnum::NotAttending],
        );

        $cycle->readingProgress()
            ->where('club_member_id', $member->id)
            ->firstOrFail()
            ->forceFill([
                'status' => ReadingProgressStatusEnum::Finished,
                'progress_percent' => 100,
                'finished_at' => now()->subDays(8),
                'updated_at' => now()->subDays(8),
            ])
            ->save();

        $response = $this->actingAs($user)->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.nextAction.type', 'none');
    }
}
