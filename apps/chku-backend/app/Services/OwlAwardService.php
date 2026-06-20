<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ReadingProgressStatusEnum;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class OwlAwardService
{
    /**
     * Award owls to the top 3 members who finished the book first.
     * Only members who attended the meeting are eligible.
     *
     * @param array<int> $attendingMemberIds
     * @return array<int, array{memberId: int, memberName: string, medal: string}>
     */
    public function awardForCompletedCycle(ReadingCycle $cycle, array $attendingMemberIds): array
    {
        if (empty($attendingMemberIds)) {
            return [];
        }

        /** @var Collection<int, ReadingProgress> $finished */
        $finished = ReadingProgress::query()
            ->with('clubMember.user')
            ->where('reading_cycle_id', $cycle->id)
            ->where('status', ReadingProgressStatusEnum::Finished)
            ->whereNotNull('finished_at')
            ->whereIn('club_member_id', $attendingMemberIds)
            ->orderBy('finished_at')
            ->orderBy('club_member_id')
            ->get();

        if ($finished->isEmpty()) {
            return [];
        }

        $medals = [
            0 => 'gold',
            1 => 'silver',
            2 => 'bronze',
        ];

        $awards = [];

        DB::transaction(function () use ($finished, $medals, &$awards): void {
            $columns = [
                0 => 'gold_owls_count',
                1 => 'silver_owls_count',
                2 => 'bronze_owls_count',
            ];

            foreach ($finished->take(3) as $index => $progress) {
                $column = $columns[$index] ?? null;
                $medal = $medals[$index] ?? null;

                if ($column === null || $medal === null) {
                    continue;
                }

                $progress->clubMember()->increment($column);

                $awards[] = [
                    'memberId' => $progress->club_member_id,
                    'memberName' => $progress->clubMember->user?->name ?? 'Участник',
                    'medal' => $medal,
                ];
            }
        });

        return $awards;
    }
}
