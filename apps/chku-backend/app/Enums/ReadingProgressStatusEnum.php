<?php

namespace App\Enums;

enum ReadingProgressStatusEnum: string
{
    case NotStarted = 'not_started';
    case Reading = 'reading';
    case Finished = 'finished';
    case Abandoned = 'abandoned';
}
