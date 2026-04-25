<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\MeetingResource;
use App\Models\Meeting;

final class MeetingController extends Controller
{
    public function show(Meeting $meeting): MeetingResource
    {
        return new MeetingResource(
            $meeting->load('readingCycle.book', 'rsvps.clubMember.user')
        );
    }
}
