<?php

namespace App\Enums;

enum MemberDeactivationReasonEnum: string
{
    case Paused = 'paused';
    case Left = 'left';
}
