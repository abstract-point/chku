<?php

namespace App\Enums;

enum MeetingRsvpStatusEnum: string
{
    case Attending = 'attending';
    case NotAttending = 'not_attending';
    case Pending = 'pending';
}
