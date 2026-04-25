<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Club;
use App\Http\Resources\ClubResource;

final class ClubController extends Controller
{
    public function show(): ClubResource
    {
        return new ClubResource(Club::with('members.user')->first());
    }
}
