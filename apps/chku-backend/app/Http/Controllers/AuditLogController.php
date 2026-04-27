<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $logs = AuditLog::with(['actor', 'targetMember.user'])
            ->latest()
            ->paginate($request->input('per_page', 25));

        return response()->json($logs);
    }
}
