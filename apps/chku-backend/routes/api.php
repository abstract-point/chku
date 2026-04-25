<?php

use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\BookCandidateController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\ClubMemberController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MeetingController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('ping'));
Route::get('ping', fn () => now())->name('ping');

Route::get('club', [ClubController::class, 'show']);

Route::get('members', [ClubMemberController::class, 'index']);
Route::get('members/{member}', [ClubMemberController::class, 'show']);

Route::get('dashboard', [DashboardController::class, 'index']);

Route::get('archive', [ArchiveController::class, 'index']);
Route::get('archive/{slug}', [ArchiveController::class, 'show']);

Route::get('meetings/{meeting}', [MeetingController::class, 'show']);

Route::get('candidates/active', [BookCandidateController::class, 'active']);
