<?php

namespace App\Enums;

enum BookCandidateResponseEnum: string
{
    case NotRead = 'not_read';
    case Read = 'read';
    case NotSure = 'not_sure';
    case Pending = 'pending';
}
