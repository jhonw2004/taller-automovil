<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SolicitudTallerController;
use App\Http\Controllers\TallerBusquedaController;
use App\Http\Controllers\TallerPerfilController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Marketplace — Búsqueda y perfil público de talleres (005, público, sin cuenta)
|--------------------------------------------------------------------------
*/

Route::get('/talleres/buscar', [TallerBusquedaController::class, 'index'])->name('talleres.buscar');
Route::get('/talleres/{slug}', [TallerPerfilController::class, 'show'])->name('talleres.show');

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

/*
|--------------------------------------------------------------------------
| Marketplace — Dashboard del usuario (006, guard `web`, usuario autenticado)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:web')->get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
