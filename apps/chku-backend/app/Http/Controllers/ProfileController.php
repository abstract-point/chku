<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Fortify\UpdateUserPassword;
use App\Http\Resources\MemberDetailResource;
use App\Models\ClubMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class ProfileController extends Controller
{
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
            'initials' => ['required', 'string', 'max:10'],
            'favorite_genre_id' => ['nullable', 'integer', Rule::exists('genres', 'id')],
        ]);

        DB::transaction(function () use ($user, $member, $payload): void {
            $user->forceFill([
                'name' => $payload['name'],
            ])->save();

            $member->forceFill([
                'initials' => $payload['initials'],
                'favorite_genre_id' => $payload['favorite_genre_id'] ?? null,
            ])->save();
        });

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
