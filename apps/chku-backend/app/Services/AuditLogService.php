<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ClubMember;
use App\Models\Meeting;
use App\Models\User;

final class AuditLogService
{
    public function logMemberCreated(ClubMember $newMember, User $actor): void
    {
        AuditLog::create([
            'actor_id' => $actor->id,
            'target_member_id' => $newMember->id,
            'action' => 'member_created',
            'metadata' => [
                'user_name' => $newMember->user?->name,
                'user_email' => $newMember->user?->email,
                'initials' => $newMember->initials,
            ],
        ]);
    }

    public function logMemberDeactivated(ClubMember $member, User $actor): void
    {
        AuditLog::create([
            'actor_id' => $actor->id,
            'target_member_id' => $member->id,
            'action' => 'member_deactivated',
            'metadata' => [
                'user_name' => $member->user?->name,
                'deactivated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    public function logMeetingCreated(Meeting $meeting, User $actor): void
    {
        AuditLog::create([
            'actor_id' => $actor->id,
            'action' => 'meeting_created',
            'metadata' => [
                'meeting_id' => $meeting->id,
                'title' => $meeting->title,
                'date' => $meeting->date?->format('Y-m-d'),
                'place' => $meeting->place,
            ],
        ]);
    }

    public function logMeetingUpdated(Meeting $meeting, User $actor, array $changes): void
    {
        AuditLog::create([
            'actor_id' => $actor->id,
            'action' => 'meeting_updated',
            'metadata' => [
                'meeting_id' => $meeting->id,
                'title' => $meeting->title,
                'changes' => $changes,
            ],
        ]);
    }
}
