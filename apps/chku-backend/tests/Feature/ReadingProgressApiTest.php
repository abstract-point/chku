<?php

namespace Tests\Feature;

use App\Enums\ReadingProgressStatusEnum;
use App\Models\ReadingCycle;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingProgressApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsFirstMember(): self
    {
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        return $this->actingAs($user);
    }

    public function test_current_member_can_update_reading_progress(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsFirstMember();

        $response = $this->patchJson('/api/reading-progress/me', [
            'progressPercent' => 48,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.progressPercent', 48)
            ->assertJsonPath('data.status', ReadingProgressStatusEnum::Reading->value);

        $currentCycle = ReadingCycle::where('status', 'active')->firstOrFail();

        $this->assertDatabaseHas('reading_progress', [
            'reading_cycle_id' => $currentCycle->id,
            'progress_percent' => 48,
            'status' => ReadingProgressStatusEnum::Reading->value,
        ]);
    }

    public function test_progress_percent_must_be_between_zero_and_one_hundred(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsFirstMember();

        $response = $this->patchJson('/api/reading-progress/me', [
            'progressPercent' => 101,
        ]);

        $response->assertUnprocessable();
    }

    public function test_setting_progress_to_one_hundred_records_finished_at(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsFirstMember();

        $response = $this->patchJson('/api/reading-progress/me', [
            'progressPercent' => 100,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', ReadingProgressStatusEnum::Finished->value);

        $currentCycle = ReadingCycle::where('status', 'active')->firstOrFail();

        $this->assertDatabaseHas('reading_progress', [
            'reading_cycle_id' => $currentCycle->id,
            'progress_percent' => 100,
            'status' => ReadingProgressStatusEnum::Finished->value,
        ]);

        $progress = \App\Models\ReadingProgress::where([
            'reading_cycle_id' => $currentCycle->id,
        ])->first();

        $this->assertNotNull($progress?->finished_at);
    }

    public function test_finished_at_is_not_overwritten_on_subsequent_updates(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAsFirstMember();

        $this->patchJson('/api/reading-progress/me', [
            'progressPercent' => 100,
        ]);

        $currentCycle = ReadingCycle::where('status', 'active')->firstOrFail();
        $firstFinishedAt = \App\Models\ReadingProgress::where('reading_cycle_id', $currentCycle->id)->value('finished_at');

        sleep(1);

        $this->patchJson('/api/reading-progress/me', [
            'progressPercent' => 100,
        ]);

        $secondFinishedAt = \App\Models\ReadingProgress::where('reading_cycle_id', $currentCycle->id)->value('finished_at');

        $this->assertEquals($firstFinishedAt, $secondFinishedAt);
    }
}
