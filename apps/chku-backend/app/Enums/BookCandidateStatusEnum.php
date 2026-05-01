<?php

namespace App\Enums;

enum BookCandidateStatusEnum: string
{
    case Pending = 'pending';
    case AwaitingOwnerConfirmation = 'awaiting_owner_confirmation';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
