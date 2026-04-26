<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ClubMember;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class CurrentMemberService
{
    public function get(): ClubMember
    {
        $email = env('CHKU_CURRENT_MEMBER_EMAIL', 'elena@example.com');

        $member = ClubMember::with('user', 'favoriteGenre')
            ->whereHas('user', fn ($query) => $query->where('email', $email))
            ->first();

        if ($member) {
            return $member;
        }

        return ClubMember::with('user', 'favoriteGenre')
            ->where('is_active', true)
            ->orderBy('id')
            ->firstOr(fn () => throw new ModelNotFoundException('No active club member found.'));
    }
}
