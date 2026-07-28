<?php

use App\Models\CredencialSistema;
use App\Models\Notificacion;
use App\Models\UsuarioSistema;

it('notifica a un usuario cuya contraseña expira dentro de 7 dias', function () {
    $usuario = UsuarioSistema::factory()->create();
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create([
        'password_expires_at' => now()->addDays(3),
        'password_changed_at' => now()->subDays(87),
    ]);

    $this->artisan('notificaciones:passwords-por-vencer')->assertSuccessful();

    expect(Notificacion::where('usuario_sistema_id', $usuario->id)->where('tipo', 'password.expirada')->count())->toBe(1);
});

it('no notifica si la contraseña expira en mas de 7 dias', function () {
    $usuario = UsuarioSistema::factory()->create();
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create([
        'password_expires_at' => now()->addDays(30),
    ]);

    $this->artisan('notificaciones:passwords-por-vencer')->assertSuccessful();

    expect(Notificacion::where('tipo', 'password.expirada')->exists())->toBeFalse();
});

it('no notifica si ya paso la fecha de expiracion', function () {
    $usuario = UsuarioSistema::factory()->create();
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create([
        'password_expires_at' => now()->subDay(),
    ]);

    $this->artisan('notificaciones:passwords-por-vencer')->assertSuccessful();

    expect(Notificacion::where('tipo', 'password.expirada')->exists())->toBeFalse();
});

it('no duplica la notificacion si se corre el comando dos veces el mismo ciclo', function () {
    $usuario = UsuarioSistema::factory()->create();
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create([
        'password_expires_at' => now()->addDays(3),
        'password_changed_at' => now()->subDays(87),
    ]);

    $this->artisan('notificaciones:passwords-por-vencer');
    $this->artisan('notificaciones:passwords-por-vencer');

    expect(Notificacion::where('usuario_sistema_id', $usuario->id)->where('tipo', 'password.expirada')->count())->toBe(1);
});

it('no notifica a un usuario inactivo', function () {
    $usuario = UsuarioSistema::factory()->create(['activo' => false]);
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create([
        'password_expires_at' => now()->addDays(3),
    ]);

    $this->artisan('notificaciones:passwords-por-vencer')->assertSuccessful();

    expect(Notificacion::where('tipo', 'password.expirada')->exists())->toBeFalse();
});
