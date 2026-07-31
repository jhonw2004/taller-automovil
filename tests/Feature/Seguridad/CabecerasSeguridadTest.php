<?php

it('toda respuesta trae las cabeceras de seguridad base', function () {
    $respuesta = $this->get('/');

    $respuesta->assertHeader('X-Content-Type-Options', 'nosniff');
    $respuesta->assertHeader('X-Frame-Options', 'DENY');
    $respuesta->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $respuesta->assertHeader('Permissions-Policy', 'geolocation=(self), camera=(), microphone=(), payment=()');
    $respuesta->assertHeaderMissing('Strict-Transport-Security');
});

it('la respuesta trae Content-Security-Policy-Report-Only, no bloqueante', function () {
    $respuesta = $this->get('/');

    $respuesta->assertHeaderMissing('Content-Security-Policy');
    expect($respuesta->headers->get('Content-Security-Policy-Report-Only'))
        ->toContain("default-src 'self'")
        ->toContain('*.tile.openstreetmap.org');
});

it('HSTS solo se agrega cuando el entorno es production', function () {
    $this->app->detectEnvironment(fn () => 'production');

    $this->get('/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('las respuestas JSON de la API tambien traen las cabeceras (middleware global, no solo grupo web)', function () {
    $this->getJson('/api/talleres/search')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY');
});
