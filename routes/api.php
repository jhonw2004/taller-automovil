<?php

use App\Http\Controllers\TallerController;
use Illuminate\Support\Facades\Route;

Route::get('/talleres', [TallerController::class, 'index']);
