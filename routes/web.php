<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

/*
|--------------------------------------------------------------------------
| Marketplace — OAuth (guard `web`)
|--------------------------------------------------------------------------
*/

Route::middleware('guest:web')->group(function () {
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth:web')->group(function () {
    Route::post('/auth/logout', [LogoutController::class, 'logout'])->name('auth.logout');
});
