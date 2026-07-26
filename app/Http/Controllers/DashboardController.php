<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * `/dashboard` (006-resenas-favoritos/tasks.md): "Mis Favoritos" + "Mis Reseñas" del usuario
 * marketplace autenticado. El layout `marketplace.layouts.auth` ya existía desde 005+016 sin
 * ninguna ruta real que lo usara — este es su primer consumidor.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();

        $favoritos = $usuario->favoritos()->with('taller.categorias')->latest()->get();
        $resenas = $usuario->resenas()->with('taller')->latest()->get();

        return view('marketplace.dashboard.index', [
            'favoritos' => $favoritos,
            'resenas' => $resenas,
        ]);
    }
}
