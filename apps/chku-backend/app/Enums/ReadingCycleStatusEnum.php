<?php

namespace App\Enums;

enum ReadingCycleStatusEnum: string
{
    case Proposed = 'proposed';
    case Active = 'active';
    case Completed = 'completed';
}
