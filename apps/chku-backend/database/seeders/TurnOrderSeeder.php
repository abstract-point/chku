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

        $previous = null;

        foreach ([
            'elena@example.com',
            'mikhail@example.com',
            'anna@example.com',
            'pavel@example.com',
            'marina@example.com',
            'admin@example.com',
        ] as $email) {
            $order = TurnOrder::create([
                'club_id' => $club->id,
                'club_member_id' => $members[$email]->id,
                'next_turn_order_id' => null,
            ]);

            $previous?->update(['next_turn_order_id' => $order->id]);
            $previous = $order;
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
