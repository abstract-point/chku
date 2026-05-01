<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BookCandidateResponseEnum;
use App\Enums\BookCandidateStatusEnum;
use App\Http\Resources\BookCandidateResource;
use App\Models\BookCandidate;
use App\Models\BookCandidateResponse;
use App\Services\CurrentMemberService;
use App\Services\BookSelectionStateMachine;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

final class BookCandidateController extends Controller
{
    public function active(): BookCandidateResource|JsonResponse
    {
        $candidate = BookCandidate::with([
            'book.genre',
            'proposer.user',
            'responses.clubMember.user',
        ])
            ->whereIn('status', [
                BookCandidateStatusEnum::Pending,
                BookCandidateStatusEnum::AwaitingOwnerConfirmation,
            ])
            ->latest()
            ->first();

        return $candidate
            ? new BookCandidateResource($candidate)
            : response()->json(null);
    }

    public function respond(
        Request $request,
        BookCandidate $candidate,
        CurrentMemberService $currentMember,
        BookSelectionStateMachine $stateMachine,
    ): BookCandidateResource {
        $payload = $request->validate([
            'response' => [
                'required',
                Rule::in([BookCandidateResponseEnum::Read->value, BookCandidateResponseEnum::NotRead->value]),
            ],
        ]);

        abort_if($candidate->status !== BookCandidateStatusEnum::Pending, 422, 'Ответить можно только на кандидата в проверке.');

        BookCandidateResponse::updateOrCreate(
            [
                'book_candidate_id' => $candidate->id,
                'club_member_id' => $currentMember->get()->id,
            ],
            [
                'response' => BookCandidateResponseEnum::from($payload['response']),
            ],
        );

        $candidate = $stateMachine->recalculateCandidate($candidate);

        return new BookCandidateResource($candidate->load([
            'book.genre',
            'proposer.user',
            'responses.clubMember.user',
        ]));
    }

    public function confirm(
        BookCandidate $candidate,
        CurrentMemberService $currentMember,
        BookSelectionStateMachine $stateMachine,
    ): BookCandidateResource|JsonResponse
    {
        abort_if($candidate->proposer_id !== $currentMember->get()->id, 403, 'Подтвердить книгу может только её владелец.');

        $candidate = $stateMachine->confirmCandidate($candidate);

        return new BookCandidateResource($candidate->load([
            'book.genre',
            'proposer.user',
            'responses.clubMember.user',
        ]));
    }
}
