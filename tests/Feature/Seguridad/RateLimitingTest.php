<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * 020-seguridad-produccion §A. Dos niveles de verificación:
 * - Los límites bajos (`publico-escritura`, 5/min) se prueban con un round-trip HTTP real —
 *   son los que hoy protegen contra spam/DoS directo sobre formularios públicos.
 * - Los límites altos (`publico-lectura` 60/min, `busqueda-api` 30/min) se verifican resolviendo
 *   el `Limit` registrado directamente — disparar 61/31 requests HTTP reales por test sería lento
 *   sin aportar una garantía distinta: ambos casos comparten el mismo middleware `throttle:*` ya
 *   ejercitado por los tests de `publico-escritura`.
 */
it('POST /solicitudes-taller devuelve 429 al superar 5 requests/min desde la misma IP', function () {
    for ($i = 1; $i <= 5; $i++) {
        $respuesta = $this->post(route('solicitudes.store'), []);
        expect($respuesta->status())->not->toBe(429);
    }

    $this->post(route('solicitudes.store'), [])->assertStatus(429);
});

it('POST /solicitudes-taller/{token}/cancelar devuelve 429 al superar 5 requests/min desde la misma IP', function () {
    // `token_publico` es una columna `uuid` nativa (ver el `whereUuid('token')` agregado a la
    // ruta) — el token debe tener forma de UUID real para que la request llegue al controlador
    // en vez de que el ruteo la rechace con 404 antes de contar contra el rate limit.
    for ($i = 1; $i <= 5; $i++) {
        $respuesta = $this->post(route('solicitudes.cancelar', (string) Str::uuid()));
        expect($respuesta->status())->not->toBe(429);
    }

    $this->post(route('solicitudes.cancelar', (string) Str::uuid()))->assertStatus(429);
});

it('un token con formato invalido (no-UUID) devuelve 404 limpio, no un 500', function () {
    // Regresión del bug real encontrado al escribir el test anterior: antes de agregar
    // `whereUuid('token')` a la ruta, un segmento no-UUID llegaba hasta
    // `SolicitudTaller::where('token_publico', $token)` — columna `uuid` nativa de Postgres — y
    // la BD rechazaba la query (`invalid input syntax for type uuid`), un PDOException sin
    // capturar que Laravel convertía en 500 crudo en vez del 404 esperado para un token
    // inexistente/mal formado.
    $this->get('/solicitudes-taller/no-es-un-uuid')->assertNotFound();
    $this->post('/solicitudes-taller/no-es-un-uuid/cancelar')->assertNotFound();
});

it('el limiter "publico-lectura" permite 60 requests por minuto por IP', function () {
    $limit = RateLimiter::limiter('publico-lectura')(Request::create('/'));

    expect($limit->maxAttempts)->toBe(60)
        ->and($limit->decaySeconds)->toBe(60);
});

it('el limiter "busqueda-api" permite 30 requests por minuto por IP', function () {
    $limit = RateLimiter::limiter('busqueda-api')(Request::create('/'));

    expect($limit->maxAttempts)->toBe(30)
        ->and($limit->decaySeconds)->toBe(60);
});

it('el limiter "marketplace-escritura" permite 10 requests por minuto por usuario o IP', function () {
    $limit = RateLimiter::limiter('marketplace-escritura')(Request::create('/'));

    expect($limit->maxAttempts)->toBe(10)
        ->and($limit->decaySeconds)->toBe(60);
});

it('el limiter "notificaciones" permite 30 requests por minuto por usuario o IP', function () {
    $limit = RateLimiter::limiter('notificaciones')(Request::create('/'));

    expect($limit->maxAttempts)->toBe(30)
        ->and($limit->decaySeconds)->toBe(60);
});
