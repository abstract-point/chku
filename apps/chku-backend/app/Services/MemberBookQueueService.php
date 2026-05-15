<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MemberBookQueueItemStatusEnum;
use App\Models\Book;
use App\Models\ClubMember;
use App\Models\MemberBookQueueItem;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class MemberBookQueueService
{
    /**
     * @return Collection<int, MemberBookQueueItem>
     */
    public function orderedLiveItems(ClubMember|int $member): Collection
    {
        return $this->orderedItems($member, [
            MemberBookQueueItemStatusEnum::InVerification,
            MemberBookQueueItemStatusEnum::Queued,
        ]);
    }

    /**
     * @param array<int, MemberBookQueueItemStatusEnum> $statuses
     * @return Collection<int, MemberBookQueueItem>
     */
    public function orderedItems(ClubMember|int $member, array $statuses): Collection
    {
        $memberId = $member instanceof ClubMember ? $member->id : $member;
        $statusValues = array_map(fn (MemberBookQueueItemStatusEnum $status): string => $status->value, $statuses);

        $items = MemberBookQueueItem::query()
            ->with('book.genre')
            ->where('club_member_id', $memberId)
            ->whereIn('status', $statusValues)
            ->get();

        return $this->buildOrderedList($items);
    }

    public function headQueuedItem(ClubMember $member): ?MemberBookQueueItem
    {
        return $this->orderedLiveItems($member)
            ->first(fn (MemberBookQueueItem $item): bool => $item->status === MemberBookQueueItemStatusEnum::Queued);
    }

    public function createAtHead(ClubMember $member, Book $book, ?string $reason, ?string $description): MemberBookQueueItem
    {
        return DB::transaction(function () use ($member, $book, $reason, $description): MemberBookQueueItem {
            $head = $this->headLiveItemForUpdate($member->id);

            return MemberBookQueueItem::create([
                'club_member_id' => $member->id,
                'book_id' => $book->id,
                'next_queue_item_id' => $head?->id,
                'reason' => $reason,
                'description' => $description,
                'status' => MemberBookQueueItemStatusEnum::Queued,
            ]);
        });
    }

    public function moveToHead(MemberBookQueueItem $item): MemberBookQueueItem
    {
        return DB::transaction(function () use ($item): MemberBookQueueItem {
            $items = $this->liveItemsForUpdate($item->club_member_id);
            $ordered = $this->buildOrderedList($items);
            $head = $ordered->first();

            if (! $head || $head->id === $item->id) {
                return $item->refresh();
            }

            /** @var MemberBookQueueItem|null $previous */
            $previous = $items->firstWhere('next_queue_item_id', $item->id);
            $nextQueueItemId = $item->next_queue_item_id;

            $item->update(['next_queue_item_id' => null]);

            if ($previous) {
                $previous->update(['next_queue_item_id' => $nextQueueItemId]);
            }

            $item->update(['next_queue_item_id' => $head->id]);

            return $item->refresh();
        });
    }

    public function removeFromLiveQueue(MemberBookQueueItem $item, MemberBookQueueItemStatusEnum $status): MemberBookQueueItem
    {
        return DB::transaction(function () use ($item, $status): MemberBookQueueItem {
            $items = $this->liveItemsForUpdate($item->club_member_id);

            /** @var MemberBookQueueItem|null $previous */
            $previous = $items->firstWhere('next_queue_item_id', $item->id);
            $nextQueueItemId = $item->next_queue_item_id;

            $item->update(['next_queue_item_id' => null]);

            if ($previous) {
                $previous->update(['next_queue_item_id' => $nextQueueItemId]);
            }

            $item->update([
                'status' => $status,
            ]);

            return $item->refresh();
        });
    }

    /**
     * @param array<int, int> $itemIds
     * @return Collection<int, MemberBookQueueItem>
     */
    public function reorder(ClubMember $member, array $itemIds): Collection
    {
        return DB::transaction(function () use ($member, $itemIds): Collection {
            $items = $this->liveItemsForUpdate($member->id)->keyBy('id');

            abort_if($items->count() !== count($itemIds), 422, 'Можно переставлять только живую очередь.');

            foreach ($itemIds as $id) {
                abort_if(! $items->has((int) $id), 403, 'Нельзя менять чужую очередь.');
            }

            foreach ($itemIds as $id) {
                $items[(int) $id]->update(['next_queue_item_id' => null]);
            }

            foreach ($itemIds as $index => $id) {
                $nextId = $itemIds[$index + 1] ?? null;
                $items[(int) $id]->update(['next_queue_item_id' => $nextId ? (int) $nextId : null]);
            }

            return $this->orderedLiveItems($member);
        });
    }

    private function headLiveItemForUpdate(int $memberId): ?MemberBookQueueItem
    {
        return $this->buildOrderedList($this->liveItemsForUpdate($memberId))->first();
    }

    private function liveItemsForUpdate(int $memberId): EloquentCollection
    {
        return MemberBookQueueItem::query()
            ->where('club_member_id', $memberId)
            ->whereIn('status', [
                MemberBookQueueItemStatusEnum::Queued->value,
                MemberBookQueueItemStatusEnum::InVerification->value,
            ])
            ->lockForUpdate()
            ->get();
    }

    /**
     * @return Collection<int, MemberBookQueueItem>
     */
    private function buildOrderedList(EloquentCollection|Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return collect();
        }

        $byId = $items->keyBy('id');
        $referencedIds = $items
            ->pluck('next_queue_item_id')
            ->filter()
            ->map(fn (int|string $id): int => (int) $id)
            ->all();

        $head = $items->first(fn (MemberBookQueueItem $item): bool => ! in_array($item->id, $referencedIds, true))
            ?? $items->sortBy('id')->first();

        $result = collect();
        $seen = [];
        $current = $head;

        while ($current && ! isset($seen[$current->id])) {
            $result->push($current);
            $seen[$current->id] = true;
            $current = $current->next_queue_item_id
                ? $byId->get($current->next_queue_item_id)
                : null;
        }

        return $result
            ->merge($items->reject(fn (MemberBookQueueItem $item): bool => isset($seen[$item->id]))->sortBy('id'))
            ->values();
    }
}
