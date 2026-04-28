<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Fortify;

final class TwoFactorController extends Controller
{
    public function enable(Request $request, EnableTwoFactorAuthentication $enable): JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $enable($user);

        return response()->json(['message' => '2FA setup started.']);
    }

    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();
        assert($user instanceof User);

        $confirm($user, $payload['code']);

        return response()->json(['message' => '2FA enabled.']);
    }

    public function disable(Request $request, DisableTwoFactorAuthentication $disable): JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $disable($user);

        return response()->json(['message' => '2FA disabled.']);
    }

    public function qrCode(Request $request): JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        return response()->json([
            'svg' => $user->twoFactorQrCodeSvg(),
            'url' => $user->twoFactorQrCodeUrl(),
        ]);
    }

    public function secretKey(Request $request): JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        return response()->json([
            'secretKey' => $user->two_factor_secret
                ? Fortify::currentEncrypter()->decrypt($user->two_factor_secret)
                : '',
        ]);
    }

    public function recoveryCodes(Request $request): JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        if (! $user->two_factor_secret || ! $user->two_factor_recovery_codes) {
            return response()->json([]);
        }

        return response()->json(json_decode(Fortify::currentEncrypter()->decrypt(
            $user->two_factor_recovery_codes
        ), true));
    }

    public function regenerateRecoveryCodes(
        Request $request,
        GenerateNewRecoveryCodes $generateNewRecoveryCodes,
    ): JsonResponse {
        $user = $request->user();
        assert($user instanceof User);

        $generateNewRecoveryCodes($user);

        return response()->json(['message' => 'Recovery codes regenerated.']);
    }
}
