<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 020-seguridad-produccion §C: cabeceras de seguridad HTTP globales. Antes de esta clase,
 * `bootstrap/app.php` no registraba ningún middleware de este tipo — ninguna respuesta traía
 * `X-Frame-Options`/`X-Content-Type-Options`/etc.
 *
 * La CSP se agrega en modo `Content-Security-Policy-Report-Only` a propósito (decisión
 * confirmada con el usuario, 2026-07-31): Alpine.js/Livewire/Filament dependen de bastante
 * inline, y sin un navegador real disponible en este entorno para verificar visualmente que
 * una política bloqueante no rompe el mapa/paneles, activarla bloqueante no es un riesgo
 * asumible en esta sesión. Pasar a bloqueante es trabajo futuro informado por los reportes
 * reales que esta política ya empieza a recolectar.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'geolocation=(self), camera=(), microphone=(), payment=()'
        );

        if (app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        $response->headers->set(
            'Content-Security-Policy-Report-Only',
            "default-src 'self'; "
            ."img-src 'self' data: *.tile.openstreetmap.org; "
            ."style-src 'self' 'unsafe-inline'; "
            ."script-src 'self' 'unsafe-inline'; "
            ."connect-src 'self'"
        );

        return $response;
    }
}
