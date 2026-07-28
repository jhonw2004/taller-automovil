<?php

use App\Actions\Identidad\CambiarPasswordAction;
use App\Actions\Roles\AsignarRolAction;
use App\Models\AuditoriaAcceso;
use App\Models\CredencialSistema;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

it('audita un login exitoso del guard sistema', function () {
    $usuario = UsuarioSistema::factory()->create(['username' => 'auditoria.login']);
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create();

    $exito = auth('sistema')->attempt(['username' => 'auditoria.login', 'password' => 'Password123!']);

    expect($exito)->toBeTrue();
    $acceso = AuditoriaAcceso::where('usuario_sistema_id', $usuario->id)->where('tipo_acceso', 'LOGIN')->first();
    expect($acceso)->not->toBeNull();
    expect($acceso->resultado)->toBe('EXITOSO');
    expect($acceso->identificador)->toBe('auditoria.login');
});

it('audita un login fallido del guard sistema', function () {
    $usuario = UsuarioSistema::factory()->create(['username' => 'auditoria.fallido']);
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create();

    auth('sistema')->attempt(['username' => 'auditoria.fallido', 'password' => 'incorrecta']);

    $acceso = AuditoriaAcceso::where('tipo_acceso', 'FAILED_LOGIN')->latest('id')->first();
    expect($acceso)->not->toBeNull();
    expect($acceso->resultado)->toBe('FALLIDO');
    expect($acceso->identificador)->toBe('auditoria.fallido');
});

it('audita un logout del guard sistema', function () {
    $usuario = UsuarioSistema::factory()->create();
    $this->actingAs($usuario, 'sistema');

    auth('sistema')->logout();

    $acceso = AuditoriaAcceso::where('usuario_sistema_id', $usuario->id)->where('tipo_acceso', 'LOGOUT')->first();
    expect($acceso)->not->toBeNull();
    expect($acceso->resultado)->toBe('EXITOSO');
});

it('audita el cambio de contraseña como PASSWORD_CHANGE exitoso', function () {
    $usuario = UsuarioSistema::factory()->create(['username' => 'auditoria.password']);
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create();

    app(CambiarPasswordAction::class)->execute($usuario, 'NuevaPassword123!');

    $acceso = AuditoriaAcceso::where('usuario_sistema_id', $usuario->id)->where('tipo_acceso', 'PASSWORD_CHANGE')->first();
    expect($acceso)->not->toBeNull();
    expect($acceso->resultado)->toBe('EXITOSO');
});

it('audita acceso denegado cuando un usuario sistema sin taller vigente pide /erp', function () {
    $usuario = UsuarioSistema::factory()->create(['username' => 'sin.acceso']);

    $this->actingAs($usuario, 'sistema')->get('/erp')->assertForbidden();

    $acceso = AuditoriaAcceso::where('usuario_sistema_id', $usuario->id)
        ->where('tipo_acceso', 'LOGIN')->where('resultado', 'DENEGADO')->first();
    expect($acceso)->not->toBeNull();
});

it('no audita acceso denegado si el usuario sistema tiene taller vigente', function () {
    $taller = Taller::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $taller->id, asignadoPor: null);

    $this->actingAs($usuario, 'sistema')->get('/erp')->assertSuccessful();

    expect(AuditoriaAcceso::where('usuario_sistema_id', $usuario->id)->where('resultado', 'DENEGADO')->exists())->toBeFalse();
});
