<?php

use App\Http\Controllers\TallerBusquedaApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marketplace — Búsqueda de talleres (005, público, sin autenticación)
|--------------------------------------------------------------------------
*/

Route::get('/talleres/search', [TallerBusquedaApiController::class, 'search'])
    ->middleware('throttle:30,1')
    ->name('api.talleres.search');
