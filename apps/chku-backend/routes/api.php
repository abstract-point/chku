<?php

use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BookCandidateController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\ClubMemberController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('ping'));
Route::get('ping', fn () => now())->name('ping');

Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:login');
Route::post('two-factor-challenge', [AuthController::class, 'twoFactorChallenge'])
    ->middleware('throttle:two-factor');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::patch('me/profile', [ProfileController::class, 'update']);
    Route::put('me/password', [ProfileController::class, 'updatePassword']);
    Route::post('me/two-factor-authentication', [TwoFactorController::class, 'enable']);
    Route::post('me/confirmed-two-factor-authentication', [TwoFactorController::class, 'confirm']);
    Route::delete('me/two-factor-authentication', [TwoFactorController::class, 'disable']);
    Route::get('me/two-factor-qr-code', [TwoFactorController::class, 'qrCode']);
    Route::get('me/two-factor-secret-key', [TwoFactorController::class, 'secretKey']);
    Route::get('me/two-factor-recovery-codes', [TwoFactorController::class, 'recoveryCodes']);
    Route::post('me/two-factor-recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes']);

    Route::get('club', [ClubController::class, 'show']);
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('genres', [GenreController::class, 'index']);

    Route::get('members', [ClubMemberController::class, 'index']);
    Route::get('members/{member}', [ClubMemberController::class, 'show']);
    Route::post('members', [ClubMemberController::class, 'store']);
    Route::post('members/{member}/deactivate', [ClubMemberController::class, 'deactivate']);

    Route::get('archive', [ArchiveController::class, 'index']);
    Route::get('archive/{slug}', [ArchiveController::class, 'show']);

    Route::get('meetings/{meeting}', [MeetingController::class, 'show']);
    Route::post('meetings', [MeetingController::class, 'store']);
    Route::patch('meetings/{meeting}', [MeetingController::class, 'update']);

    Route::get('candidates/active', [BookCandidateController::class, 'active']);
    Route::post('candidates', [BookCandidateController::class, 'store']);
    Route::patch('candidates/{candidate}/responses/me', [BookCandidateController::class, 'respond']);
    Route::post('candidates/{candidate}/approve', [BookCandidateController::class, 'approve']);

    Route::get('audit-logs', [AuditLogController::class, 'index']);
});
