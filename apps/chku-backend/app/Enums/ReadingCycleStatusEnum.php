<?php

namespace App\Enums;

enum ReadingCycleStatusEnum: string
{
    case Proposed = 'proposed';
    case Verifying = 'verifying';
    case Active = 'active';
    case Completed = 'completed';
}
