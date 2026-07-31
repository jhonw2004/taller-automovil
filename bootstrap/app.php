<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 020-seguridad-produccion §C: cabeceras de seguridad en toda respuesta, incluidas las
        // de `routes/api.php` (grupo `api`, sin sesión) — se agrega al stack global, no solo
        // al grupo `web`, para no dejar sin cubrir ningún endpoint JSON público.
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // `is('api/*')` cubre los endpoints JSON "puros"; `expectsJson()` (Accept: application/json,
        // como cualquier fetch() de Alpine) cubre los que viven en routes/web.php pero se consumen
        // por JS — sin el segundo term, un fetch() no autenticado a una ruta fuera de `/api/*`
        // (ej. `/notificaciones/{id}/marcar-leida`, 014-notificaciones) intentaba redirigir a
        // `route('login')` (inexistente en este proyecto, solo hay OAuth) y explotaba con 500 en
        // vez de un 401/422 limpio.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
