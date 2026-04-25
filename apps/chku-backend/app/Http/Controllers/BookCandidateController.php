<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\BookCandidateResource;
use App\Models\BookCandidate;
use Illuminate\Http\JsonResponse;

final class BookCandidateController extends Controller
{
    public function active(): BookCandidateResource|JsonResponse
    {
        $candidate = BookCandidate::with([
            'book.genre',
            'proposer.user',
            'responses.clubMember.user',
        ])
            ->where('status', 'pending')
            ->latest()
            ->first();

        return $candidate
            ? new BookCandidateResource($candidate)
            : response()->json(null);
    }
}
