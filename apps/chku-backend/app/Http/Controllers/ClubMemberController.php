<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClubMember;
use App\Http\Resources\MemberDetailResource;
use App\Http\Resources\MemberResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ClubMemberController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return MemberResource::collection(
            ClubMember::with('user', 'favoriteGenre')->get()
        );
    }

    public function show(ClubMember $member): MemberDetailResource
    {
        return new MemberDetailResource(
            $member->load('user', 'favoriteGenre', 'readingProgress', 'proposedCycles', 'meetingRsvps')
        );
    }
}
