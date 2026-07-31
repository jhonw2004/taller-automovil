<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificacionController;
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

Route::get('/talleres/buscar', [TallerBusquedaController::class, 'index'])
    ->name('talleres.buscar')->middleware('throttle:publico-lectura');
Route::get('/talleres/{slug}', [TallerPerfilController::class, 'show'])
    ->name('talleres.show')->middleware('throttle:publico-lectura');

/*
|--------------------------------------------------------------------------
| Marketplace — Solicitud de alta de taller (004, público, sin cuenta)
|--------------------------------------------------------------------------
*/

Route::prefix('solicitudes-taller')->name('solicitudes.')->group(function () {
    Route::get('/nueva', [SolicitudTallerController::class, 'create'])->name('create')
        ->middleware('throttle:publico-lectura');
    Route::post('/', [SolicitudTallerController::class, 'store'])->name('store')
        ->middleware('throttle:publico-escritura');
    // 020-seguridad-produccion §D: `token_publico` es una columna `uuid` nativa de Postgres
    // (`2026_07_26_070001_create_solicitudes_taller_table.php`) — sin esta restricción, un
    // segmento que no tiene forma de UUID (ej. `/solicitudes-taller/abc`) llegaba hasta
    // `SolicitudTaller::where('token_publico', $token)->firstOrFail()` y Postgres rechazaba la
    // query con `invalid input syntax for type uuid`, un `PDOException` sin capturar → 500 crudo
    // en vez del 404 esperado para un token inexistente. Encontrado con un test real (no
    // hipotético), corregido en la ruta para que ni siquiera llegue al controlador.
    Route::get('/{token}', [SolicitudTallerController::class, 'seguimiento'])->name('seguimiento')
        ->middleware('throttle:publico-lectura')->whereUuid('token');
    Route::post('/{token}/cancelar', [SolicitudTallerController::class, 'cancelar'])->name('cancelar')
        ->middleware('throttle:publico-escritura')->whereUuid('token');
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

/*
|--------------------------------------------------------------------------
| Notificaciones (014, guards `web` y `sistema` — ver NotificacionController)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:web,sistema', 'throttle:notificaciones'])
    ->post('/notificaciones/{notificacion}/marcar-leida', [NotificacionController::class, 'marcarLeida'])
    ->name('notificaciones.marcar-leida');
