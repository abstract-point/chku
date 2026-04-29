<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReadingCycleStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Http\Resources\ReadingProgressResource;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use App\Services\CurrentMemberService;
use Illuminate\Http\Request;

final class ReadingProgressController extends Controller
{
    public function updateCurrent(Request $request, CurrentMemberService $currentMember): ReadingProgressResource
    {
        $payload = $request->validate([
            'progressPercent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $cycle = ReadingCycle::query()
            ->where('status', ReadingCycleStatusEnum::Active)
            ->firstOrFail();

        $member = $currentMember->get();
        $progressPercent = (int) $payload['progressPercent'];

        $progress = ReadingProgress::updateOrCreate(
            [
                'reading_cycle_id' => $cycle->id,
                'club_member_id' => $member->id,
            ],
            [
                'progress_percent' => $progressPercent,
                'status' => $this->statusForProgress($progressPercent),
            ],
        );

        return new ReadingProgressResource($progress->load('clubMember.user'));
    }

    private function statusForProgress(int $progressPercent): ReadingProgressStatusEnum
    {
        return match (true) {
            $progressPercent >= 100 => ReadingProgressStatusEnum::Finished,
            $progressPercent === 0 => ReadingProgressStatusEnum::NotStarted,
            default => ReadingProgressStatusEnum::Reading,
        };
    }
}
