<?php

namespace App\Enums;

enum MemberBookQueueItemStatusEnum: string
{
    case Queued = 'queued';
    case InVerification = 'in_verification';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Removed = 'removed';
}
