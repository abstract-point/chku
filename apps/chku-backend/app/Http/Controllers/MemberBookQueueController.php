<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MemberBookQueueItemStatusEnum;
use App\Http\Resources\MemberBookQueueItemResource;
use App\Models\Book;
use App\Models\Genre;
use App\Models\MemberBookQueueItem;
use App\Services\CurrentMemberService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class MemberBookQueueController extends Controller
{
    public function index(CurrentMemberService $currentMember): AnonymousResourceCollection
    {
        return MemberBookQueueItemResource::collection(
            $currentMember->get()
                ->bookQueueItems()
                ->with('book.genre')
                ->where('status', '!=', MemberBookQueueItemStatusEnum::Removed->value)
                ->orderBy('position')
                ->get()
        );
    }

    public function store(Request $request, CurrentMemberService $currentMember): MemberBookQueueItemResource
    {
        $payload = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $member = $currentMember->get();

        $item = DB::transaction(function () use ($payload, $member): MemberBookQueueItem {
            $book = Book::firstOrCreate(
                ['slug' => $this->uniqueSlug($payload['title'])],
                [
                    'title' => $payload['title'],
                    'author' => $payload['author'],
                    'description' => $payload['description'] ?? null,
                    'genre_id' => Genre::where('slug', 'fiction')->value('id'),
                    'cover_color' => '#3a405a',
                ],
            );

            return MemberBookQueueItem::create([
                'club_member_id' => $member->id,
                'book_id' => $book->id,
                'position' => (int) $member->bookQueueItems()->max('position') + 1,
                'reason' => $payload['reason'] ?? null,
                'description' => $payload['description'] ?? null,
                'status' => MemberBookQueueItemStatusEnum::Queued,
            ]);
        });

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

    public function destroy(MemberBookQueueItem $item, CurrentMemberService $currentMember): MemberBookQueueItemResource
    {
        $this->authorizeOwner($item, $currentMember->get()->id);
        $item->update(['status' => MemberBookQueueItemStatusEnum::Removed]);

        return new MemberBookQueueItemResource($item->refresh()->load('book.genre'));
    }

    public function reorder(Request $request, CurrentMemberService $currentMember): AnonymousResourceCollection
    {
        $payload = $request->validate([
            'itemIds' => ['required', 'array', 'min:1'],
            'itemIds.*' => ['integer', Rule::exists('member_book_queue_items', 'id')],
        ]);

        $member = $currentMember->get();
        $items = $member->bookQueueItems()
            ->whereIn('id', $payload['itemIds'])
            ->get()
            ->keyBy('id');

        abort_if($items->count() !== count($payload['itemIds']), 403, 'Нельзя менять чужую очередь.');

        DB::transaction(function () use ($payload, $items): void {
            foreach ($payload['itemIds'] as $index => $id) {
                $items[(int) $id]->update(['position' => 10000 + $index]);
            }

            foreach ($payload['itemIds'] as $index => $id) {
                $items[(int) $id]->update(['position' => $index + 1]);
            }
        });

        return $this->index($currentMember);
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
}
