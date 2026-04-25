<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\DashboardResource;
use App\Services\DashboardService;

final class DashboardController extends Controller
{
    public function index(DashboardService $service): DashboardResource
    {
        return DashboardResource::make($service->getData());
    }
}
