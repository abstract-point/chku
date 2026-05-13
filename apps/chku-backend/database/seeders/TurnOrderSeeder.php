<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\ClubMember;
use App\Models\TurnOrder;
use Illuminate\Database\Seeder;

class TurnOrderSeeder extends Seeder
{
    public function run(): void
    {
        $club = Club::first();
        $members = $this->getMembers();

        TurnOrder::create([
            'club_id' => $club->id,
            'club_member_id' => $members['П1']->id,
            'position' => 1,
            'is_current' => true,
        ]);

        TurnOrder::create([
            'club_id' => $club->id,
            'club_member_id' => $members['П2']->id,
            'position' => 2,
            'is_next' => true,
        ]);

        TurnOrder::create([
            'club_id' => $club->id,
            'club_member_id' => $members['АД']->id,
            'position' => 3,
        ]);
    }

    private function getMembers(): array
    {
        $members = [];
        foreach (ClubMember::all() as $member) {
            $members[$member->initials] = $member;
        }
        return $members;
    }
}
