<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MemberBookQueueItemStatusEnum;
use App\Http\Resources\MemberBookQueueItemResource;
use App\Models\Book;
use App\Models\Genre;
use App\Models\MemberBookQueueItem;
use App\Services\BookSelectionStateMachine;
use App\Services\CurrentMemberService;
use App\Services\MemberBookQueueService;
use App\Services\TurnOrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class MemberBookQueueController extends Controller
{
    public function index(CurrentMemberService $currentMember, MemberBookQueueService $queue): AnonymousResourceCollection
    {
        return MemberBookQueueItemResource::collection(
            $queue->orderedLiveItems($currentMember->get())
        );
    }

    public function rejected(CurrentMemberService $currentMember, MemberBookQueueService $queue): AnonymousResourceCollection
    {
        return MemberBookQueueItemResource::collection(
            $queue->rejectedItems($currentMember->get())
        );
    }

    public function store(
        Request $request,
        CurrentMemberService $currentMember,
        BookSelectionStateMachine $stateMachine,
        MemberBookQueueService $queue,
        TurnOrderService $turnOrder,
    ): MemberBookQueueItemResource {
        $payload = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'coverUrl' => ['nullable', 'url', 'max:2048'],
        ]);

        $member = $currentMember->get();

        $book = Book::firstOrCreate(
            ['slug' => $this->uniqueSlug($payload['title'])],
            $this->bookPayload($payload),
        );

        $item = $queue->createAtHead(
            $member,
            $book,
            $payload['reason'] ?? null,
            $payload['description'] ?? null,
        );

        $clubId = $member->club_id;
        $currentSelector = $turnOrder->currentSelector($clubId);
        if ($currentSelector && $currentSelector->id === $member->id) {
            $stateMachine->syncPendingCandidateWithQueueHead($member);
        }

        return new MemberBookQueueItemResource($item->load('book.genre'));
    }

    public function update(
        Request $request,
        MemberBookQueueItem $item,
        CurrentMemberService $currentMember,
    ): MemberBookQueueItemResource {
        $this->authorizeOwner($item, $currentMember->get()->id);

        $payload = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $item->update($payload);

        return new MemberBookQueueItemResource($item->refresh()->load('book.genre'));
    }

    public function destroy(
        MemberBookQueueItem $item,
        CurrentMemberService $currentMember,
        MemberBookQueueService $queue,
    ): MemberBookQueueItemResource
    {
        $this->authorizeOwner($item, $currentMember->get()->id);
        $item = $queue->removeFromLiveQueue($item, MemberBookQueueItemStatusEnum::Removed);

        return new MemberBookQueueItemResource($item->refresh()->load('book.genre'));
    }

    public function reorder(
        Request $request,
        CurrentMemberService $currentMember,
        MemberBookQueueService $queue,
        BookSelectionStateMachine $stateMachine,
        TurnOrderService $turnOrder,
    ): AnonymousResourceCollection
    {
        $payload = $request->validate([
            'itemIds' => ['required', 'array', 'min:1'],
            'itemIds.*' => ['integer', Rule::exists('member_book_queue_items', 'id')],
        ]);

        $member = $currentMember->get();
        $queue->reorder($member, array_map('intval', $payload['itemIds']));

        $currentSelector = $turnOrder->currentSelector($member->club_id);
        if ($currentSelector && $currentSelector->id === $member->id) {
            $stateMachine->syncPendingCandidateWithQueueHead($member);
        }

        return $this->index($currentMember, $queue);
    }

    public function makeCandidate(
        MemberBookQueueItem $item,
        CurrentMemberService $currentMember,
        MemberBookQueueService $queue,
        BookSelectionStateMachine $stateMachine,
        TurnOrderService $turnOrder,
    ): MemberBookQueueItemResource {
        $member = $currentMember->get();
        $this->authorizeOwner($item, $member->id);
        abort_if($item->status !== MemberBookQueueItemStatusEnum::Queued, 422, 'Кандидатом можно сделать только книгу в очереди.');

        $item = $queue->moveToHead($item);

        $currentSelector = $turnOrder->currentSelector($member->club_id);
        if ($currentSelector && $currentSelector->id === $member->id) {
            $stateMachine->makeQueueItemCandidate($item);
        }

        return new MemberBookQueueItemResource($item->refresh()->load('book.genre'));
    }

    private function authorizeOwner(MemberBookQueueItem $item, int $memberId): void
    {
        abort_if($item->club_member_id !== $memberId, 403, 'Нельзя менять чужую очередь.');
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'book';
        $slug = $base;
        $index = 2;

        while (Book::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$index}";
            $index++;
        }

        return $slug;
    }

    private function bookPayload(array $payload): array
    {
        return [
            'title' => $payload['title'],
            'author' => $payload['author'],
            'slug' => $this->uniqueSlug($payload['title']),
            'description' => $payload['description'] ?? null,
            'genre_id' => Genre::where('slug', 'fiction')->value('id'),
            'cover_color' => '#3a405a',
            'cover_url' => $payload['coverUrl'] ?? null,
        ];
    }
}
