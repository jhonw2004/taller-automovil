<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\SolicitudTallerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

/*
|--------------------------------------------------------------------------
| Marketplace — Solicitud de alta de taller (004, público, sin cuenta)
|--------------------------------------------------------------------------
*/

Route::prefix('solicitudes-taller')->name('solicitudes.')->group(function () {
    Route::get('/nueva', [SolicitudTallerController::class, 'create'])->name('create');
    Route::post('/', [SolicitudTallerController::class, 'store'])->name('store');
    Route::get('/{token}', [SolicitudTallerController::class, 'seguimiento'])->name('seguimiento');
    Route::post('/{token}/cancelar', [SolicitudTallerController::class, 'cancelar'])->name('cancelar');
});

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
