<?php

namespace Tests\Feature;

use App\Enums\MeetingRsvpStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
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
}
