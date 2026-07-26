<?php

use App\Models\CredencialSistema;
use App\Models\UsuarioSistema;

it('fuerza el cambio de contraseña en el login si password_expires_at ya pasó', function () {
    $usuario = UsuarioSistema::factory()->create(['username' => 'expirado1']);
    $credencial = CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create([
        'debe_cambiar_password' => false,
        'password_expires_at' => now()->subDay(),
    ]);

    $exito = auth('sistema')->attempt(['username' => 'expirado1', 'password' => 'Password123!']);
    expect($exito)->toBeTrue();

    $credencial->refresh();
    expect($credencial->debe_cambiar_password)->toBeTrue();
});

it('no fuerza el cambio si la contraseña todavía no expiró', function () {
    $usuario = UsuarioSistema::factory()->create(['username' => 'vigente1']);
    $credencial = CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create([
        'debe_cambiar_password' => false,
        'password_expires_at' => now()->addDays(30),
    ]);

    auth('sistema')->attempt(['username' => 'vigente1', 'password' => 'Password123!']);

    $credencial->refresh();
    expect($credencial->debe_cambiar_password)->toBeFalse();
});
