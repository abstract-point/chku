<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ClubMember;
use App\Models\ReadingCycle;
use App\Models\TurnOrder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class TurnOrderService
{
    public function orderedTurnOrders(int $clubId): Collection
    {
        $orders = TurnOrder::query()
            ->with('clubMember.user')
            ->where('club_id', $clubId)
            ->whereHas('clubMember', fn ($query) => $query->where('is_active', true))
            ->get();

        return $this->buildOrderedList($orders);
    }

    public function currentSelector(int $clubId): ?ClubMember
    {
        return $this->orderedTurnOrders($clubId)->first()?->clubMember;
    }

    public function nextSelector(int $clubId): ?ClubMember
    {
        return $this->orderedTurnOrders($clubId)->skip(1)->first()?->clubMember;
    }

    public function rotateAfterCompletedCycle(ReadingCycle $cycle): void
    {
        DB::transaction(function () use ($cycle): void {
            $orders = TurnOrder::query()
                ->where('club_id', $cycle->club_id)
                ->whereHas('clubMember', fn ($query) => $query->where('is_active', true))
                ->lockForUpdate()
                ->get();

            $ordered = $this->buildOrderedList($orders);
            if ($ordered->count() < 2) {
                return;
            }

            /** @var TurnOrder|null $order */
            $order = $orders->firstWhere('club_member_id', $cycle->proposer_id);
            if (! $order || $order->next_turn_order_id === null) {
                return;
            }

            /** @var TurnOrder $tail */
            $tail = $ordered->last();

            /** @var TurnOrder|null $previous */
            $previous = $orders->firstWhere('next_turn_order_id', $order->id);
            $nextTurnOrderId = $order->next_turn_order_id;

            $order->update(['next_turn_order_id' => null]);

            if ($previous) {
                $previous->update(['next_turn_order_id' => $nextTurnOrderId]);
            }

            $tail->update(['next_turn_order_id' => $order->id]);
        });
    }

    public function appendMember(ClubMember $member): TurnOrder
    {
        return DB::transaction(function () use ($member): TurnOrder {
            $orders = TurnOrder::query()
                ->where('club_id', $member->club_id)
                ->lockForUpdate()
                ->get();

            $existing = $orders->firstWhere('club_member_id', $member->id);
            if ($existing) {
                return $existing;
            }

            $order = TurnOrder::create([
                'club_id' => $member->club_id,
                'club_member_id' => $member->id,
                'next_turn_order_id' => null,
            ]);

            $ordered = $this->buildOrderedList($orders);
            $tail = $ordered->last();

            if ($tail) {
                $tail->update(['next_turn_order_id' => $order->id]);
            }

            return $order;
        });
    }

    public function removeMember(ClubMember $member): void
    {
        DB::transaction(function () use ($member): void {
            $orders = TurnOrder::query()
                ->where('club_id', $member->club_id)
                ->lockForUpdate()
                ->get();

            /** @var TurnOrder|null $order */
            $order = $orders->firstWhere('club_member_id', $member->id);
            if (! $order) {
                return;
            }

            /** @var TurnOrder|null $previous */
            $previous = $orders->firstWhere('next_turn_order_id', $order->id);
            $nextTurnOrderId = $order->next_turn_order_id;

            if ($nextTurnOrderId !== null) {
                $order->update(['next_turn_order_id' => null]);
            }

            if ($previous) {
                $previous->update(['next_turn_order_id' => $nextTurnOrderId]);
            }

            $order->delete();
        });
    }

    private function buildOrderedList(EloquentCollection|Collection $orders): Collection
    {
        if ($orders->isEmpty()) {
            return collect();
        }

        $byId = $orders->keyBy('id');
        $referencedIds = $orders
            ->pluck('next_turn_order_id')
            ->filter()
            ->map(fn (int|string $id): int => (int) $id)
            ->all();

        $head = $orders->first(fn (TurnOrder $order): bool => ! in_array($order->id, $referencedIds, true))
            ?? $orders->sortBy('id')->first();

        $result = collect();
        $seen = [];
        $current = $head;

        while ($current && ! isset($seen[$current->id])) {
            $result->push($current);
            $seen[$current->id] = true;
            $current = $current->next_turn_order_id
                ? $byId->get($current->next_turn_order_id)
                : null;
        }

        return $result
            ->merge($orders->reject(fn (TurnOrder $order): bool => isset($seen[$order->id]))->sortBy('id'))
            ->values();
    }
}
