<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MeetingRsvpStatusEnum;
use App\Enums\ReadingCycleStatusEnum;
use App\Models\ClubMember;
use App\Models\ReadingCycle;
use Illuminate\Support\Collection;

final class MemberCycleHistoryService
{
    public function forMember(ClubMember $member): Collection
    {
        return ReadingCycle::query()
            ->with([
                'book.genre',
                'proposer.user',
                'ratings',
                'reviews',
                'readingProgress',
                'meeting.rsvps',
            ])
            ->where('status', ReadingCycleStatusEnum::Completed)
            ->where(function ($query) use ($member): void {
                $query
                    ->whereHas('readingProgress', fn ($q) => $q->where('club_member_id', $member->id))
                    ->orWhereHas('ratings', fn ($q) => $q->where('club_member_id', $member->id))
                    ->orWhereHas('reviews', fn ($q) => $q->where('club_member_id', $member->id));
            })
            ->orderByDesc('cycle_number')
            ->get()
            ->map(fn (ReadingCycle $cycle) => $this->mapCycle($cycle, $member));
    }

    private function mapCycle(ReadingCycle $cycle, ClubMember $member): array
    {
        $memberRating = $cycle->ratings->firstWhere('club_member_id', $member->id);
        $memberReview = $cycle->reviews->firstWhere('club_member_id', $member->id);
        $memberRsvp = $cycle->meeting?->rsvps->firstWhere('club_member_id', $member->id);
        $averageRating = $cycle->ratings->avg('rating');

        return [
            'title' => $cycle->book?->title,
            'coverTitle' => $cycle->book?->title,
            'author' => $cycle->book?->author,
            'coverColor' => $cycle->book?->cover_color,
            'cycleNumber' => $cycle->cycle_number,
            'cycleLabel' => "Цикл #{$cycle->cycle_number}",
            'completedLabel' => $cycle->completed_at?->translatedFormat('F Y')
                ?? "Цикл #{$cycle->cycle_number}",
            'proposedBy' => $cycle->proposer?->user?->name,
            'myRating' => $memberRating?->rating,
            'clubAverageRating' => $averageRating === null ? null : round((float) $averageRating, 1),
            'hasReview' => $memberReview !== null,
            'meetingRsvpStatus' => $memberRsvp?->status?->value,
            'attendedMeeting' => $memberRsvp?->status === MeetingRsvpStatusEnum::Attending,
        ];
    }
}
