<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ClubMember;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class CurrentMemberService
{
    public function get(): ClubMember
    {
        $user = auth()->user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $member = ClubMember::with('user', 'favoriteGenre')
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            throw new ModelNotFoundException('No club member found for authenticated user.');
        }

        return $member;
    }

    public function getOptional(): ?ClubMember
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        return ClubMember::with('user', 'favoriteGenre')
            ->where('user_id', $user->id)
            ->first();
    }
}
