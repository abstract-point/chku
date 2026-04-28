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
        return $this->canManageMembers($user);
    }

    public function deactivate(User $user, ClubMember $clubMember): bool
    {
        return $this->canManageMembers($user);
    }

    private function canManageMembers(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'developer'])
            && $user->two_factor_confirmed_at !== null;
    }
}
