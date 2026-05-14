<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ClubMember;

final class MemberAvatar
{
    public static function url(mixed $member): ?string
    {
        if (! $member instanceof ClubMember) {
            return null;
        }

        if (! $member?->user?->avatar_path) {
            return null;
        }

        return route('members.avatar', ['member' => $member->id], absolute: false);
    }
}
