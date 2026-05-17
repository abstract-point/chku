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
     */
    public function awardForCompletedCycle(ReadingCycle $cycle, array $attendingMemberIds): void
    {
        if (empty($attendingMemberIds)) {
            return;
        }

        /** @var Collection<int, ReadingProgress> $finished */
        $finished = ReadingProgress::query()
            ->where('reading_cycle_id', $cycle->id)
            ->where('status', ReadingProgressStatusEnum::Finished)
            ->whereNotNull('finished_at')
            ->whereIn('club_member_id', $attendingMemberIds)
            ->orderBy('finished_at')
            ->get();

        if ($finished->isEmpty()) {
            return;
        }

        $medals = [
            0 => 'gold_owls_count',
            1 => 'silver_owls_count',
            2 => 'bronze_owls_count',
        ];

        DB::transaction(function () use ($finished, $medals): void {
            foreach ($finished->take(3) as $index => $progress) {
                $column = $medals[$index] ?? null;

                if ($column === null) {
                    continue;
                }

                $progress->clubMember()->increment($column);
            }
        });
    }
}
