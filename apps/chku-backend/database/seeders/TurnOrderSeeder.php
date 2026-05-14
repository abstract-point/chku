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
            'club_member_id' => $members['elena@example.com']->id,
            'position' => 1,
            'is_current' => true,
        ]);

        TurnOrder::create([
            'club_id' => $club->id,
            'club_member_id' => $members['mikhail@example.com']->id,
            'position' => 2,
            'is_next' => true,
        ]);

        TurnOrder::create([
            'club_id' => $club->id,
            'club_member_id' => $members['admin@example.com']->id,
            'position' => 3,
        ]);
    }

    private function getMembers(): array
    {
        $members = [];
        foreach (ClubMember::with('user')->get() as $member) {
            $members[$member->user->email] = $member;
        }
        return $members;
    }
}
