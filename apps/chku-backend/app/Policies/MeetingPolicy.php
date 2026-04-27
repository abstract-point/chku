<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;

class MeetingPolicy
{
    public function view(User $user, Meeting $meeting): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'developer']);
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $user->hasAnyRole(['admin', 'developer']);
    }
}
