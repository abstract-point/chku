<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\MemberDetailResource;
use App\Models\ClubMember;
use App\Models\User;
use App\Services\CurrentMemberService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($payload, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        assert($user instanceof User);

        // Check if 2FA is required for this user (admin/developer roles)
        if ($this->requiresTwoFactor($user) && ! $user->two_factor_confirmed_at) {
            // 2FA is required but not set up — allow login but flag in response
            // Or we could enforce it. For now, just return user info.
        }

        if ($this->requiresTwoFactor($user) && $user->two_factor_secret) {
            // Partial login: return two_factor_required flag
            // Actual 2FA verification happens via Fortify's /two-factor-challenge
            return response()->json([
                'two_factor_required' => true,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                ],
            ]);
        }

        $member = ClubMember::with('user', 'favoriteGenre')
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'user' => $member ? new MemberDetailResource($member) : null,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getPermissionNames(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Выход выполнен.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $member = ClubMember::with('user', 'favoriteGenre', 'readingProgress', 'proposedCycles', 'meetingRsvps')
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getPermissionNames(),
                'clubMember' => null,
            ]);
        }

        return response()->json([
            'user' => new MemberDetailResource($member),
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getPermissionNames(),
            'twoFactorEnabled' => $user->two_factor_confirmed_at !== null,
        ]);
    }

    private function requiresTwoFactor(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('developer');
    }
}
