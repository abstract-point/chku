<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('ping'));
Route::get('ping', fn() => now())->name('ping');