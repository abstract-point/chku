<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookCandidateResponseEnum;
use App\Enums\BookCandidateStatusEnum;
use App\Enums\MemberDeactivationReasonEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Models\BookCandidate;
use App\Models\ClubMember;
use App\Models\MeetingRsvp;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use Illuminate\Support\Facades\DB;

final class MembershipService
{
    public function __construct(
        private readonly TurnOrderService $turnOrder,
        private readonly BookSelectionStateMachine $stateMachine,
    ) {
    }

    /**
     * Pause or end a membership: the member leaves the turn order, current meeting and candidate check.
     */
    public function deactivate(ClubMember $member, MemberDeactivationReasonEnum $reason): void
    {
        DB::transaction(function () use ($member, $reason): void {
            $member->update([
                'is_active' => false,
                'deactivated_at' => now(),
                'deactivation_reason' => $reason,
            ]);

            $this->turnOrder->removeMember($member);

            MeetingRsvp::query()
                ->where('club_member_id', $member->id)
                ->whereHas('meeting', fn ($q) => $q
                    ->whereNull('finished_at')
                    ->whereHas('readingCycle', fn ($c) => $c->where('status', ReadingCycleStatusEnum::Active)))
                ->delete();

            if (! $this->stateMachine->withdrawCandidateOf($member)) {
                // The member's pending answer no longer blocks the check.
                $this->stateMachine->recalculateActiveCandidate($member->club_id);
            }
        });
    }

    /**
     * Bring a member back: they go to the end of the turn order and join the current cycle.
     */
    public function activate(ClubMember $member): void
    {
        DB::transaction(function () use ($member): void {
            $member->update([
                'is_active' => true,
                'deactivated_at' => null,
                'deactivation_reason' => null,
            ]);

            $this->turnOrder->appendMember($member);
            $this->addToCurrentCycle($member);
        });
    }

    /**
     * Make an active member part of the current cycle: a pending answer for the candidate check
     * or a reading progress entry for the book being read.
     *
     * @return bool|null null when there is no current cycle to join
     */
    public function addToCurrentCycle(ClubMember $member): ?bool
    {
        $cycle = ReadingCycle::query()
            ->where('club_id', $member->club_id)
            ->whereNot('status', ReadingCycleStatusEnum::Completed)
            ->first();

        if (! $cycle) {
            return null;
        }

        if ($cycle->status === ReadingCycleStatusEnum::Proposed) {
            $candidate = $this->currentCycleCandidate($cycle, [
                BookCandidateStatusEnum::Pending,
                BookCandidateStatusEnum::AwaitingOwnerConfirmation,
            ]);

            return $candidate ? $this->ensureCandidateResponse($candidate, $member) : null;
        }

        $created = false;
        $candidate = $this->currentCycleCandidate($cycle, [
            BookCandidateStatusEnum::Approved,
            BookCandidateStatusEnum::AwaitingOwnerConfirmation,
            BookCandidateStatusEnum::Pending,
        ]);
        if ($candidate) {
            $created = $this->ensureCandidateResponse($candidate, $member);
        }

        $progress = ReadingProgress::firstOrCreate(
            ['reading_cycle_id' => $cycle->id, 'club_member_id' => $member->id],
            ['status' => ReadingProgressStatusEnum::NotStarted, 'progress_percent' => 0],
        );

        return $created || $progress->wasRecentlyCreated;
    }

    private function currentCycleCandidate(ReadingCycle $cycle, array $statuses): ?BookCandidate
    {
        return $cycle->bookCandidate()
            ->whereIn('status', array_map(fn (BookCandidateStatusEnum $status): string => $status->value, $statuses))
            ->latest()
            ->first();
    }

    private function ensureCandidateResponse(BookCandidate $candidate, ClubMember $member): bool
    {
        $response = $candidate->responses()->firstOrCreate(
            ['club_member_id' => $member->id],
            ['response' => BookCandidateResponseEnum::Pending],
        );

        // A returning member may still have a stale answer: the check is open again until they confirm.
        if ($candidate->status === BookCandidateStatusEnum::AwaitingOwnerConfirmation
            && $response->response !== BookCandidateResponseEnum::NotRead) {
            $candidate->update(['status' => BookCandidateStatusEnum::Pending]);
            $this->stateMachine->recalculateCandidate($candidate);
        }

        return $response->wasRecentlyCreated;
    }
}
