<?php

namespace Tests\Unit;

use App\Enums\ReadingProgressStatusEnum;
use App\Models\ClubMember;
use App\Models\Meeting;
use App\Models\MeetingRsvp;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use App\Services\OwlAwardService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwlAwardServiceTest extends TestCase
{
    use RefreshDatabase;

    private OwlAwardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OwlAwardService();
    }

    public function test_awards_gold_silver_bronze_to_top_three_finishers(): void
    {
        $this->seed(DatabaseSeeder::class);

        $cycle = ReadingCycle::where('status', 'active')->firstOrFail();
        $members = ClubMember::where('club_id', $cycle->club_id)
            ->where('is_active', true)
            ->take(3)
            ->get();

        $attendingIds = [];
        foreach ($members as $index => $member) {
            $attendingIds[] = $member->id;
            $progress = $member->readingProgress()->where('reading_cycle_id', $cycle->id)->first();
            if ($progress) {
                $progress->update([
                    'progress_percent' => 100,
                    'status' => ReadingProgressStatusEnum::Finished,
                    'finished_at' => now()->subMinutes(10 - $index),
                ]);
            }
        }

        $this->service->awardForCompletedCycle($cycle, $attendingIds);

        $this->assertEquals(1, $members[0]->refresh()->gold_owls_count);
        $this->assertEquals(1, $members[1]->refresh()->silver_owls_count);
        $this->assertEquals(1, $members[2]->refresh()->bronze_owls_count);
    }

    public function test_only_attending_members_receive_owls(): void
    {
        $this->seed(DatabaseSeeder::class);

        $cycle = ReadingCycle::where('status', 'active')->firstOrFail();
        $members = ClubMember::where('club_id', $cycle->club_id)
            ->where('is_active', true)
            ->take(2)
            ->get();

        foreach ($members as $member) {
            $progress = $member->readingProgress()->where('reading_cycle_id', $cycle->id)->first();
            if ($progress) {
                $progress->update([
                    'progress_percent' => 100,
                    'status' => ReadingProgressStatusEnum::Finished,
                    'finished_at' => now(),
                ]);
            }
        }

        // Only first member is attending
        $this->service->awardForCompletedCycle($cycle, [$members[0]->id]);

        $this->assertEquals(1, $members[0]->refresh()->gold_owls_count);
        $this->assertEquals(0, $members[1]->refresh()->gold_owls_count);
    }

    public function test_no_owls_when_no_one_finished(): void
    {
        $this->seed(DatabaseSeeder::class);

        $cycle = ReadingCycle::where('status', 'active')->firstOrFail();
        $member = ClubMember::where('club_id', $cycle->club_id)
            ->where('is_active', true)
            ->first();

        $this->service->awardForCompletedCycle($cycle, [$member->id]);

        $this->assertEquals(0, $member->refresh()->gold_owls_count);
        $this->assertEquals(0, $member->refresh()->silver_owls_count);
        $this->assertEquals(0, $member->refresh()->bronze_owls_count);
    }

    public function test_no_owls_when_attending_list_is_empty(): void
    {
        $this->seed(DatabaseSeeder::class);

        $cycle = ReadingCycle::where('status', 'active')->firstOrFail();
        $member = ClubMember::where('club_id', $cycle->club_id)
            ->where('is_active', true)
            ->first();

        $progress = $member->readingProgress()->where('reading_cycle_id', $cycle->id)->first();
        if ($progress) {
            $progress->update([
                'progress_percent' => 100,
                'status' => ReadingProgressStatusEnum::Finished,
                'finished_at' => now(),
            ]);
        }

        $this->service->awardForCompletedCycle($cycle, []);

        $this->assertEquals(0, $member->refresh()->gold_owls_count);
    }

    public function test_owls_are_cumulative(): void
    {
        $this->seed(DatabaseSeeder::class);

        $cycle = ReadingCycle::where('status', 'active')->firstOrFail();
        $member = ClubMember::where('club_id', $cycle->club_id)
            ->where('is_active', true)
            ->first();

        $member->update(['gold_owls_count' => 2]);

        $progress = $member->readingProgress()->where('reading_cycle_id', $cycle->id)->first();
        if ($progress) {
            $progress->update([
                'progress_percent' => 100,
                'status' => ReadingProgressStatusEnum::Finished,
                'finished_at' => now(),
            ]);
        }

        $this->service->awardForCompletedCycle($cycle, [$member->id]);

        $this->assertEquals(3, $member->refresh()->gold_owls_count);
    }
}
