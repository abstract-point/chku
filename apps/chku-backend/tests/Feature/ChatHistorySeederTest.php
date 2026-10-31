<?php

namespace Tests\Feature;

use App\Enums\ReadingCycleStatusEnum;
use App\Models\ReadingCycle;
use App\Models\TurnOrder;
use App\Services\TurnOrderService;
use Database\Seeders\ChatHistorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ChatHistorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_ten_cycle_keeps_ivan_as_current_selector_and_denis_next(): void
    {
        $this->seed(ChatHistorySeeder::class);

        $cycle = ReadingCycle::query()
            ->with('proposer.user', 'book')
            ->where('status', ReadingCycleStatusEnum::Active)
            ->firstOrFail();

        $this->assertSame('Тень', $cycle->book->title);
        $this->assertSame('ivan@chku.local', $cycle->proposer->user->email);
        $this->assertSame([
            'ivan@chku.local',
            'denis@chku.local',
            'alexander@chku.local',
            'andrey@chku.local',
            'nastya@chku.local',
        ], $this->turnOrderEmails($cycle->club_id));
    }

    public function test_seeder_refuses_to_run_in_production(): void
    {
        $this->app['env'] = 'production';

        try {
            $this->seed(ChatHistorySeeder::class);
            $this->fail('ChatHistorySeeder must not run in production.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('must not run in production', $exception->getMessage());
        }

        $this->assertDatabaseMissing('users', ['email' => 'ivan@chku.local']);
    }

    private function turnOrderEmails(int $clubId): array
    {
        return app(TurnOrderService::class)
            ->orderedTurnOrders($clubId)
            ->map(fn (TurnOrder $order) => $order->clubMember?->user?->email)
            ->all();
    }
}
