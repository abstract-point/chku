<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireActiveMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->clubMember || ! $user->clubMember->is_active) {
            return response()->json([
                'message' => 'Действие доступно только активным участникам клуба.',
            ], 403);
        }

        return $next($request);
    }
}
