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

        foreach ([
            'elena@example.com',
            'mikhail@example.com',
            'anna@example.com',
            'pavel@example.com',
            'marina@example.com',
            'admin@example.com',
        ] as $index => $email) {
            TurnOrder::create([
                'club_id' => $club->id,
                'club_member_id' => $members[$email]->id,
                'position' => $index + 1,
                'is_current' => $index === 0,
                'is_next' => $index === 1,
            ]);
        }
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
