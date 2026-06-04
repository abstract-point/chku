<?php

namespace Tests\Feature;

use App\Enums\BookCandidateResponseEnum;
use App\Enums\BookCandidateStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Models\BookCandidate;
use App\Models\BookCandidateResponse;
use App\Models\ClubMember;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use App\Models\TurnOrder;
use App\Models\User;
use App\Services\BookSelectionStateMachine;
use App\Services\TurnOrderService;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClubMemberAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(bool $withTwoFactor = true): self
    {
        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();
        $user->assignRole('admin');

        if ($withTwoFactor) {
            $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        }

        return $this->actingAs($user)->startSession();
    }

    public function test_admin_can_create_member(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/members', [
            'name' => 'Новый Участник',
            'email' => 'new@example.com',
            'password' => 'secret123',
            'joined_at' => '2024-01-15',
            'role' => 'member',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.avatarUrl', null);
        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'member_created']);
        $this->assertSame('new@example.com', collect($this->turnOrderEmails())->last());
    }

    public function test_admin_can_create_member_with_avatar(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();

        $response = $this->post('/api/members', [
            'name' => 'Новый Участник',
            'email' => 'new@example.com',
            'password' => 'secret123',
            'avatar' => UploadedFile::fake()->image('avatar.png', 300, 420),
            'joined_at' => '2024-01-15',
            'role' => 'member',
        ]);

        $response->assertCreated();
        $user = User::where('email', 'new@example.com')->firstOrFail();

        $response->assertJsonPath('data.avatarUrl', "/api/members/{$user->clubMember->id}/avatar");
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_members_list_places_inactive_members_last(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/members');

        $response->assertOk();
        $members = collect($response->json('data'));

        $this->assertTrue($members->take(3)->every(fn (array $member) => $member['isActive'] === true));
        $this->assertSame(
            ['Анна Соколова', 'Игорь Фомин', 'Марина Светлова', 'Мария Лебедева', 'Павел Иванов'],
            $members->slice(3)->pluck('name')->all(),
        );
    }

    public function test_inactive_members_are_not_in_turn_order_after_seeding(): void
    {
        $this->actingAsAdmin();

        $emails = $this->turnOrderEmails();

        $this->assertCount(3, $emails);
        $this->assertNotContains('anna@example.com', $emails);
        $this->assertNotContains('pavel@example.com', $emails);
        $this->assertNotContains('marina@example.com', $emails);
        $this->assertNotContains('igor@example.com', $emails);
        $this->assertNotContains('maria@example.com', $emails);
    }

    public function test_member_cannot_create_member(): void
    {
        $this->seed(TestDatabaseSeeder::class);
        $user = User::where('email', 'elena@example.com')->firstOrFail();

        $response = $this->actingAs($user)->startSession()->postJson('/api/members', [
            'name' => 'Новый Участник',
            'email' => 'new@example.com',
            'password' => 'secret123',
            'joined_at' => '2024-01-15',
            'role' => 'member',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_without_two_factor_cannot_create_member(): void
    {
        $this->actingAsAdmin(withTwoFactor: false);

        $response = $this->postJson('/api/members', [
            'name' => 'Новый Участник',
            'email' => 'new@example.com',
            'password' => 'secret123',
            'joined_at' => '2024-01-15',
            'role' => 'member',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_deactivate_member(): void
    {
        $this->actingAsAdmin();
        $member = ClubMember::whereHas('user', fn ($q) => $q->where('email', 'mikhail@example.com'))->firstOrFail();

        $response = $this->postJson("/api/members/{$member->id}/deactivate");

        $response->assertOk();
        $this->assertDatabaseHas('club_members', [
            'id' => $member->id,
            'is_active' => false,
        ]);
        $this->assertNotContains('mikhail@example.com', $this->turnOrderEmails());
        $this->assertDatabaseHas('audit_logs', ['action' => 'member_deactivated']);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $this->actingAsAdmin();
        $member = ClubMember::whereHas('user', fn ($q) => $q->where('email', 'elena@example.com'))->firstOrFail();

        $response = $this->postJson("/api/members/{$member->id}/deactivate");

        $response->assertUnprocessable();
    }

    public function test_admin_without_two_factor_cannot_deactivate_member(): void
    {
        $this->actingAsAdmin(withTwoFactor: false);
        $member = ClubMember::whereHas('user', fn ($q) => $q->where('email', 'mikhail@example.com'))->firstOrFail();

        $response = $this->postJson("/api/members/{$member->id}/deactivate");

        $response->assertForbidden();
    }

    public function test_admin_can_add_member_to_proposed_cycle_without_reading_progress(): void
    {
        $this->actingAsAdmin();
        $candidate = $this->createProposedCandidate();
        $member = $this->activateMemberByEmail('anna@example.com');

        $response = $this->postJson("/api/members/{$member->id}/add-to-current-cycle");

        $response->assertOk();
        $response->assertJsonPath('message', 'Участник добавлен в цикл.');
        $this->assertDatabaseHas('book_candidate_responses', [
            'book_candidate_id' => $candidate->id,
            'club_member_id' => $member->id,
            'response' => BookCandidateResponseEnum::Pending->value,
        ]);
        $this->assertDatabaseMissing('reading_progress', [
            'reading_cycle_id' => $candidate->reading_cycle_id,
            'club_member_id' => $member->id,
        ]);
    }

    public function test_admin_adds_member_to_proposed_cycle_idempotently(): void
    {
        $this->actingAsAdmin();
        $candidate = $this->createProposedCandidate();
        $member = $this->activateMemberByEmail('anna@example.com');

        $this->postJson("/api/members/{$member->id}/add-to-current-cycle")->assertOk();
        $this->postJson("/api/members/{$member->id}/add-to-current-cycle")
            ->assertOk()
            ->assertJsonPath('message', 'Участник уже добавлен в цикл.');

        $this->assertSame(1, BookCandidateResponse::query()
            ->where('book_candidate_id', $candidate->id)
            ->where('club_member_id', $member->id)
            ->count());
    }

    public function test_adding_member_to_awaiting_candidate_returns_it_to_pending(): void
    {
        $this->actingAsAdmin();
        $candidate = $this->createProposedCandidate();
        BookCandidateResponse::where('book_candidate_id', $candidate->id)->update([
            'response' => BookCandidateResponseEnum::NotRead->value,
        ]);
        $candidate->update(['status' => BookCandidateStatusEnum::AwaitingOwnerConfirmation]);
        $member = $this->activateMemberByEmail('anna@example.com');

        $this->postJson("/api/members/{$member->id}/add-to-current-cycle")->assertOk();

        $this->assertDatabaseHas('book_candidates', [
            'id' => $candidate->id,
            'status' => BookCandidateStatusEnum::Pending->value,
        ]);
    }

    public function test_admin_can_add_member_to_active_cycle_with_reading_progress(): void
    {
        $this->actingAsAdmin();
        $cycle = ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail();
        $candidate = BookCandidate::where('reading_cycle_id', $cycle->id)->first();
        $member = $this->activateMemberByEmail('anna@example.com');

        $response = $this->postJson("/api/members/{$member->id}/add-to-current-cycle");

        $response->assertOk();
        $this->assertDatabaseHas('reading_progress', [
            'reading_cycle_id' => $cycle->id,
            'club_member_id' => $member->id,
        ]);

        if ($candidate) {
            $this->assertDatabaseHas('book_candidate_responses', [
                'book_candidate_id' => $candidate->id,
                'club_member_id' => $member->id,
                'response' => BookCandidateResponseEnum::Pending->value,
            ]);
        }
    }

    private function turnOrderEmails(): array
    {
        $clubId = ClubMember::whereHas('user', fn ($query) => $query->where('email', 'elena@example.com'))
            ->value('club_id');

        return app(TurnOrderService::class)
            ->orderedTurnOrders($clubId)
            ->map(fn (TurnOrder $order) => $order->clubMember?->user?->email)
            ->all();
    }

    private function createProposedCandidate(): BookCandidate
    {
        $cycle = ReadingCycle::where('status', ReadingCycleStatusEnum::Active)->firstOrFail();
        $cycle->update([
            'status' => ReadingCycleStatusEnum::Completed,
            'completed_at' => now(),
        ]);

        app(TurnOrderService::class)->rotateAfterCompletedCycle($cycle);
        app(BookSelectionStateMachine::class)->createCandidateForCurrentSelector($cycle->club_id);

        return BookCandidate::where('status', BookCandidateStatusEnum::Pending)->latest()->firstOrFail();
    }

    private function activateMemberByEmail(string $email): ClubMember
    {
        $member = ClubMember::whereHas('user', fn ($query) => $query->where('email', $email))->firstOrFail();
        $member->update([
            'is_active' => true,
            'deactivated_at' => null,
        ]);

        return $member->refresh();
    }
}
