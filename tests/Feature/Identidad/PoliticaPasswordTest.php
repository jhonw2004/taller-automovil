<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

function passwordRule(): Password
{
    return Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised();
}

/**
 * `Http::fake()` resuelve el PRIMER stub que matchea, no el más específico — por eso cada test
 * registra su propio fake autocontenido en vez de compartir uno global vía beforeEach (un fake
 * wildcard registrado antes taparía cualquier fake más específico de un test individual).
 */
function fakeNoComprometida(): void
{
    Http::fake(fn () => Http::response('', 200));
}

it('rechaza una contraseña de menos de 12 caracteres', function () {
    fakeNoComprometida();
    $validator = Validator::make(['password' => 'Aa1!aaaa'], ['password' => passwordRule()]);

    expect($validator->fails())->toBeTrue();
});

it('rechaza una contraseña sin mayúsculas', function () {
    fakeNoComprometida();
    $validator = Validator::make(['password' => 'contrasena123!'], ['password' => passwordRule()]);

    expect($validator->fails())->toBeTrue();
});

it('rechaza una contraseña sin símbolos', function () {
    fakeNoComprometida();
    $validator = Validator::make(['password' => 'Contrasena123'], ['password' => passwordRule()]);

    expect($validator->fails())->toBeTrue();
});

it('rechaza una contraseña que aparece en la lista de contraseñas comprometidas', function () {
    // SHA1 de "Password123!" en mayúsculas: primeros 5 chars son el prefijo consultado a la API,
    // el resto es el sufijo que debe aparecer en la respuesta fake para simular "comprometida".
    $hash = strtoupper(sha1('Password123!'));
    $prefix = substr($hash, 0, 5);
    $suffix = substr($hash, 5);

    Http::fake(function ($request) use ($prefix, $suffix) {
        return str_contains($request->url(), "range/{$prefix}")
            ? Http::response("{$suffix}:12345")
            : Http::response('');
    });

    $validator = Validator::make(['password' => 'Password123!'], ['password' => passwordRule()]);

    expect($validator->fails())->toBeTrue();
});

it('acepta una contraseña que cumple todas las reglas y no está comprometida', function () {
    fakeNoComprometida();
    $validator = Validator::make(['password' => 'Tr4ct0r$Verde9'], ['password' => passwordRule()]);

    expect($validator->passes())->toBeTrue();
});
