<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ArchiveBookResource;
use App\Models\ReadingCycle;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ArchiveController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ArchiveBookResource::collection(
            ReadingCycle::with([
                'book.genre',
                'proposer.user',AnonymousResourceCollection
                'meeting',
                'reviews.clubMember.user',
                'discussionMessages.clubMember.user',
                'ratings',
            ])
                ->where('status', 'completed')
                ->orderByDesc('cycle_number')
                ->get()
        );
    }

    public function show(string $slug): ArchiveBookResource
    {
        $cycle = ReadingCycle::with([
            'book.genre',
            'proposer.user',
            'meeting',
            'reviews.clubMember.user',
            'discussionMessages.clubMember.user',
            'ratings',
        ])
            ->whereHas('book', fn ($q) => $q->where('slug', $slug))
            ->where('status', 'completed')
            ->firstOrFail();

        return new ArchiveBookResource($cycle);
    }
}
