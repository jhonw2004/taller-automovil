<?php

use App\Models\CredencialSistema;
use App\Models\HistorialPassword;
use App\Models\UsuarioSistema;

it('password_hash no aparece en toArray()/toJson() de CredencialSistema', function () {
    $usuario = UsuarioSistema::factory()->create();
    $credencial = CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create();

    expect($credencial->toArray())->not->toHaveKey('password_hash');
    expect($credencial->toJson())->not->toContain($credencial->getAttributes()['password_hash']);
});

it('password_hash no aparece en toArray()/toJson() de HistorialPassword', function () {
    $usuario = UsuarioSistema::factory()->create();
    $historial = HistorialPassword::create([
        'usuario_sistema_id' => $usuario->id,
        'password_hash' => bcrypt('algo'),
    ]);

    expect($historial->toArray())->not->toHaveKey('password_hash');
    expect($historial->toJson())->not->toContain($historial->getAttributes()['password_hash']);
});
