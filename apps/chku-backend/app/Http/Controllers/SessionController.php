<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\MemberDetailResource;
use App\Services\CurrentMemberService;

final class SessionController extends Controller
{
    public function me(CurrentMemberService $currentMember): MemberDetailResource
    {
        return new MemberDetailResource(
            $currentMember->get()->load('user', 'favoriteGenre', 'readingProgress', 'proposedCycles', 'meetingRsvps')
        );
    }
}
