<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\MemberDetailResource;
use App\Models\ClubMember;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

final class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', $payload['email'])
            ->first();

        if (! $user || ! Hash::check($payload['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        if ($this->requiresTwoFactor($user) && $user->two_factor_confirmed_at !== null && $user->two_factor_secret) {
            $request->session()->put([
                'login.id' => $user->id,
                'login.remember' => $request->boolean('remember'),
            ]);

            return response()->json([
                'two_factor_required' => true,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                ],
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $member = ClubMember::with('user', 'favoriteGenre')
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'user' => $member ? new MemberDetailResource($member) : null,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getPermissionNames(),
            'twoFactorEnabled' => $user->two_factor_confirmed_at !== null,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Выход выполнен.']);
    }

    public function twoFactorChallenge(
        Request $request,
        TwoFactorAuthenticationProvider $twoFactorAuthenticationProvider,
    ): JsonResponse {
        $payload = $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $userId = $request->session()->get('login.id');

        if (! $userId) {
            throw ValidationException::withMessages([
                'code' => ['Сессия подтверждения 2FA не найдена.'],
            ]);
        }

        $user = User::query()->find($userId);

        if (! $user || ! $user->two_factor_secret) {
            throw ValidationException::withMessages([
                'code' => ['Сессия подтверждения 2FA недействительна.'],
            ]);
        }

        $isValidCode = ! empty($payload['code'])
            && $twoFactorAuthenticationProvider->verify(
                Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
                $payload['code'],
            );

        $recoveryCode = ! empty($payload['recovery_code']) && $user->two_factor_recovery_codes
            ? collect($user->recoveryCodes())->first(
                fn (string $code) => hash_equals($code, $payload['recovery_code'])
            )
            : null;

        if (! $isValidCode && ! $recoveryCode) {
            throw ValidationException::withMessages([
                'code' => ['Неверный код двухфакторной защиты.'],
            ]);
        }

        if ($recoveryCode) {
            $user->replaceRecoveryCode($recoveryCode);
        }

        Auth::login($user, $request->session()->pull('login.remember', false));
        $request->session()->forget('login.id');
        $request->session()->regenerate();

        return response()->json(['message' => '2FA подтверждена.']);
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
                'twoFactorEnabled' => $user->two_factor_confirmed_at !== null,
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
