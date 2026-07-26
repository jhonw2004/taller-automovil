<?php

use App\Models\CredencialSistema;
use App\Models\UsuarioSistema;

it('bloquea al usuario sistema 15 minutos tras 5 intentos fallidos consecutivos', function () {
    $usuario = UsuarioSistema::factory()->create(['username' => 'bloqueo1']);
    $credencial = CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create();

    for ($i = 0; $i < 4; $i++) {
        auth('sistema')->attempt(['username' => 'bloqueo1', 'password' => 'incorrecta']);
    }
    $credencial->refresh();
    expect($credencial->intentos_fallidos)->toBe(4);
    expect($credencial->bloqueado_hasta)->toBeNull();

    auth('sistema')->attempt(['username' => 'bloqueo1', 'password' => 'incorrecta']);
    $credencial->refresh();
    expect($credencial->intentos_fallidos)->toBe(5);
    expect($credencial->bloqueado_hasta)->not->toBeNull();
    expect($credencial->bloqueado_hasta->isFuture())->toBeTrue();

    $exito = auth('sistema')->attempt(['username' => 'bloqueo1', 'password' => 'Password123!']);
    expect($exito)->toBeFalse();
});

it('permite el login y reinicia el contador tras desbloqueo manual', function () {
    $usuario = UsuarioSistema::factory()->create(['username' => 'desbloqueo1']);
    $credencial = CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create([
        'intentos_fallidos' => 5,
        'bloqueado_hasta' => now()->addMinutes(15),
    ]);

    $credencial->update(['bloqueado_hasta' => null]);

    $exito = auth('sistema')->attempt(['username' => 'desbloqueo1', 'password' => 'Password123!']);
    expect($exito)->toBeTrue();

    $credencial->refresh();
    expect($credencial->intentos_fallidos)->toBe(0);
});
