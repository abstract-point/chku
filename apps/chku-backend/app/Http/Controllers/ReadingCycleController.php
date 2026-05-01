<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReadingCycleStatusEnum;
use App\Http\Resources\ReadingCycleResource;
use App\Models\Rating;
use App\Models\ReadingCycle;
use App\Models\Review;
use App\Services\BookSelectionStateMachine;
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
            'rating' => ['required', 'integer', 'min:1', 'max:10'],
            'review' => ['nullable', 'string', 'max:2000'],
        ]);

        $cycle = ReadingCycle::query()
            ->where('status', ReadingCycleStatusEnum::Active)
            ->firstOrFail();

        $member = $currentMember->get();

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

    public function completeCurrent(
        Request $request,
        BookSelectionStateMachine $stateMachine,
    ): ReadingCycleResource|JsonResponse {
        abort_unless($request->user()?->hasAnyRole(['admin', 'developer']), 403);

        $cycle = ReadingCycle::query()
            ->where('status', ReadingCycleStatusEnum::Active)
            ->firstOrFail();

        $activeMemberIds = $cycle->club->members()
            ->where('is_active', true)
            ->pluck('id');

        $ratedMemberIds = $cycle->ratings()
            ->whereIn('club_member_id', $activeMemberIds)
            ->pluck('club_member_id');

        $missing = $activeMemberIds->diff($ratedMemberIds);
        if ($missing->isNotEmpty()) {
            return response()->json([
                'message' => 'Цикл можно завершить только после оценок всех активных участников.',
                'missingMemberIds' => $missing->values(),
            ], 422);
        }

        $cycle->update([
            'status' => ReadingCycleStatusEnum::Completed,
            'completed_at' => now(),
        ]);

        $stateMachine->createCandidateFromNextSelector($cycle->club_id);

        return new ReadingCycleResource($cycle->refresh()->load('book.genre', 'proposer.user'));
    }
}
