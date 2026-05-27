<?php

namespace Tests\Feature;

use App\Enums\ReadingProgressStatusEnum;
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
}
