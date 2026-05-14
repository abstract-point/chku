<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Fortify\UpdateUserPassword;
use App\Http\Resources\MemberDetailResource;
use App\Models\ClubMember;
use App\Models\User;
use App\Services\MemberCycleHistoryService;
use App\Services\UserAvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class ProfileController extends Controller
{
    public function readingHistory(Request $request, MemberCycleHistoryService $history): JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $member = ClubMember::query()
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            return response()->json([
                'message' => 'Профиль участника не найден.',
            ], 404);
        }

        return response()->json([
            'data' => $history->forMember($member),
        ]);
    }

    public function update(Request $request): MemberDetailResource|JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $member = ClubMember::query()
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            return response()->json([
                'message' => 'Профиль участника не найден.',
            ], 404);
        }

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'favorite_genre_id' => ['nullable', 'integer', Rule::exists('genres', 'id')],
        ]);

        DB::transaction(function () use ($user, $member, $payload): void {
            $user->forceFill([
                'name' => $payload['name'],
            ])->save();

            $member->forceFill([
                'favorite_genre_id' => $payload['favorite_genre_id'] ?? null,
            ])->save();
        });

        return new MemberDetailResource(
            $member->refresh()->load('user', 'favoriteGenre', 'readingProgress', 'proposedCycles', 'meetingRsvps')
        );
    }

    public function updateAvatar(Request $request, UserAvatarService $avatars): MemberDetailResource|JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $member = ClubMember::query()
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            return response()->json([
                'message' => 'Профиль участника не найден.',
            ], 404);
        }

        $payload = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $path = $avatars->store($user, $payload['avatar']);

        $user->forceFill([
            'avatar_path' => $path,
        ])->save();

        return new MemberDetailResource(
            $member->refresh()->load('user', 'favoriteGenre', 'readingProgress', 'proposedCycles', 'meetingRsvps')
        );
    }

    public function updatePassword(Request $request, UpdateUserPassword $updateUserPassword): JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $updateUserPassword->update($user, $request->all());

        return response()->json([
            'message' => 'Пароль обновлён.',
        ]);
    }
}
