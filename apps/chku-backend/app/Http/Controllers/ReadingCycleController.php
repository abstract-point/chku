<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReadingCycleStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Models\Rating;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use App\Models\Review;
use App\Services\CurrentMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ReadingCycleController extends Controller
{
    public function updateCurrentRatingReview(
        Request $request,
        CurrentMemberService $currentMember,
    ): JsonResponse {
        $payload = $request->validate([
            'rating' => ['required', 'numeric', 'min:1', 'max:10'],
            'review' => ['nullable', 'string', 'max:10000'],
        ]);

        $cycle = ReadingCycle::query()
            ->where('status', ReadingCycleStatusEnum::Active)
            ->firstOrFail();

        $member = $currentMember->get();

        $hasFinished = ReadingProgress::query()
            ->where('reading_cycle_id', $cycle->id)
            ->where('club_member_id', $member->id)
            ->where('status', ReadingProgressStatusEnum::Finished)
            ->exists();
        abort_unless($hasFinished, 422, 'Оценку можно поставить только после того, как дочитаешь книгу.');

        DB::transaction(function () use ($cycle, $member, $payload): void {
            Rating::updateOrCreate(
                ['reading_cycle_id' => $cycle->id, 'club_member_id' => $member->id],
                ['rating' => $payload['rating']],
            );

            $review = trim((string) ($payload['review'] ?? ''));
            if ($review !== '') {
                Review::updateOrCreate(
                    ['reading_cycle_id' => $cycle->id, 'club_member_id' => $member->id],
                    ['text' => $review],
                );
            }
        });

        return response()->json(['message' => 'Оценка сохранена.']);
    }
}
