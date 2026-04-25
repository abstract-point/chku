<?php

namespace App\Enums;

enum BookCandidateStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
