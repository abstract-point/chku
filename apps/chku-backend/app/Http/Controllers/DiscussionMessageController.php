<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\DiscussionMessageResource;
use App\Models\DiscussionMessage;
use App\Models\ReadingCycle;
use App\Services\CurrentMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DiscussionMessageController extends Controller
{
    public function store(
        Request $request,
        CurrentMemberService $currentMember,
        int $cycle,
    ): DiscussionMessageResource|JsonResponse {
        $member = $currentMember->get();

        $readingCycle = ReadingCycle::where('cycle_number', $cycle)
            ->where('club_id', $member->club_id)
            ->firstOrFail();

        $payload = $request->validate([
            'text' => ['required', 'string', 'max:2000'],
            'parentId' => ['nullable', 'integer', 'exists:discussion_messages,id'],
        ]);

        if (isset($payload['parentId'])) {
            $parent = DiscussionMessage::findOrFail($payload['parentId']);

            if ($parent->reading_cycle_id !== $readingCycle->id) {
                return response()->json([
                    'message' => 'Нельзя ответить на сообщение из другого цикла.',
                ], 422);
            }

            if ($parent->parent_id !== null) {
                return response()->json([
                    'message' => 'Нельзя ответить на ответ. Отвечайте только на основные сообщения.',
                ], 422);
            }
        }

        $message = DiscussionMessage::create([
            'reading_cycle_id' => $readingCycle->id,
            'club_member_id' => $member->id,
            'text' => $payload['text'],
            'parent_id' => $payload['parentId'] ?? null,
        ]);

        $message->load('clubMember.user', 'replies.clubMember.user');

        return new DiscussionMessageResource($message);
    }
}
