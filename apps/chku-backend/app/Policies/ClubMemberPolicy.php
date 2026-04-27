<?php

namespace App\Policies;

use App\Models\ClubMember;
use App\Models\User;

class ClubMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ClubMember $clubMember): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'developer']);
    }

    public function deactivate(User $user, ClubMember $clubMember): bool
    {
        return $user->hasAnyRole(['admin', 'developer']);
    }
}
