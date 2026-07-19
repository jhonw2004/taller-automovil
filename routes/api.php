<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TallerController;

Route::get('/talleres', [TallerController::class, 'index']);
