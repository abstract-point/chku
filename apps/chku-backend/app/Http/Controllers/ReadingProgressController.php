<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReadingCycleStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Events\MemberFinishedReading;
use App\Events\ReadingProgressUpdated;
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

        $status = $this->statusForProgress($progressPercent);

        $attributes = [
            'reading_cycle_id' => $cycle->id,
            'club_member_id' => $member->id,
        ];

        $values = [
            'progress_percent' => $progressPercent,
            'status' => $status,
        ];

        $justFinished = false;
        if ($status === ReadingProgressStatusEnum::Finished) {
            $existing = ReadingProgress::where($attributes)->first();
            if ($existing === null || $existing->finished_at === null) {
                $values['finished_at'] = now();
                $justFinished = true;
            }
        }

        $progress = ReadingProgress::updateOrCreate($attributes, $values);

        $progress->load('clubMember.user', 'readingCycle.book');

        // Finishing the book gets a single "finished" notification instead of a 100% progress one.
        if ($justFinished) {
            event(new MemberFinishedReading($progress));
        } elseif ($status !== ReadingProgressStatusEnum::Finished) {
            event(new ReadingProgressUpdated($progress));
        }

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
