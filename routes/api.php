<?php

use App\Http\Controllers\FavoritoApiController;
use App\Http\Controllers\ResenaApiController;
use App\Http\Controllers\TallerBusquedaApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketplace — Búsqueda de talleres (005, público, sin autenticación)
|--------------------------------------------------------------------------
*/

Route::get('/talleres/search', [TallerBusquedaApiController::class, 'search'])
    ->middleware('throttle:busqueda-api')
    ->name('api.talleres.search');

/*
|--------------------------------------------------------------------------
| Marketplace — Reseñas y favoritos (006, guard `web`, usuario autenticado)
|--------------------------------------------------------------------------
| El grupo `api` (bootstrap/app.php) no arranca sesión ni CSRF por defecto — a diferencia de
| `/api/talleres/search` (público, stateless), estas rutas necesitan leer la sesión del usuario
| marketplace autenticado por OAuth vía `web.php`. Se agrega el middleware `web` explícito para
| tener `StartSession`/`VerifyCsrfToken`; el fetch de Alpine debe enviar `X-CSRF-TOKEN`.
*/

Route::middleware(['web', 'auth:web', 'throttle:marketplace-escritura'])->group(function () {
    Route::post('/resenas', [ResenaApiController::class, 'storeOrUpdate'])->name('api.resenas.store');
    Route::delete('/resenas/{resena}', [ResenaApiController::class, 'destroy'])->name('api.resenas.destroy');
    Route::post('/favoritos', [FavoritoApiController::class, 'store'])->name('api.favoritos.store');
    Route::post('/favoritos/delete', [FavoritoApiController::class, 'destroy'])->name('api.favoritos.destroy');
});
