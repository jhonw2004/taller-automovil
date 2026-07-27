<?php

use App\Actions\Identidad\ActivarDesactivarUsuarioSistemaAction;
use App\Actions\Identidad\RestablecerPasswordUsuarioAction;
use App\Actions\Roles\AsignarRolAction;
use App\Exceptions\BusinessException;
use App\Models\CredencialSistema;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\Hash;

function usuarioAsignadoAlTaller(int $tallerId, array $usuarioAttrs = []): UsuarioSistema
{
    $usuario = UsuarioSistema::factory()->create($usuarioAttrs);
    $rol = Rol::factory()->create(['taller_id' => null]);
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

// --- RestablecerPasswordUsuarioAction ---

it('RestablecerPasswordUsuarioAction: genera una nueva password temporal y exige cambiarla', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioAsignadoAlTaller($taller->id);
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create(['debe_cambiar_password' => false]);

    $passwordTemporal = app(RestablecerPasswordUsuarioAction::class)->execute($usuario, $taller->id);

    $credencial = $usuario->credencialSistema->fresh();
    expect(Hash::check($passwordTemporal, $credencial->password_hash))->toBeTrue();
    expect($credencial->debe_cambiar_password)->toBeTrue();
});

it('RestablecerPasswordUsuarioAction: rechaza si el usuario no pertenece al taller', function () {
    $taller = Taller::factory()->create();
    $otroTaller = Taller::factory()->create();
    $usuario = usuarioAsignadoAlTaller($otroTaller->id);
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create();

    expect(fn () => app(RestablecerPasswordUsuarioAction::class)->execute($usuario, $taller->id))
        ->toThrow(BusinessException::class);
});

// --- ActivarDesactivarUsuarioSistemaAction ---

it('ActivarDesactivarUsuarioSistemaAction: desactiva y el usuario desactivado no puede iniciar sesion', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioAsignadoAlTaller($taller->id, ['username' => 'empleado.activo']);
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create();

    expect(auth('sistema')->attempt(['username' => 'empleado.activo', 'password' => 'Password123!']))->toBeTrue();
    auth('sistema')->logout();

    app(ActivarDesactivarUsuarioSistemaAction::class)->execute($usuario, $taller->id, false);

    expect($usuario->fresh()->activo)->toBeFalse();
    expect(auth('sistema')->attempt(['username' => 'empleado.activo', 'password' => 'Password123!']))->toBeFalse();
});

it('ActivarDesactivarUsuarioSistemaAction: reactiva un usuario y vuelve a poder iniciar sesion', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioAsignadoAlTaller($taller->id, ['username' => 'empleado.reactivado']);
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create();
    $usuario->update(['activo' => false]);

    expect(auth('sistema')->attempt(['username' => 'empleado.reactivado', 'password' => 'Password123!']))->toBeFalse();

    app(ActivarDesactivarUsuarioSistemaAction::class)->execute($usuario, $taller->id, true);

    expect(auth('sistema')->attempt(['username' => 'empleado.reactivado', 'password' => 'Password123!']))->toBeTrue();
});

it('ActivarDesactivarUsuarioSistemaAction: rechaza si el usuario no pertenece al taller', function () {
    $taller = Taller::factory()->create();
    $otroTaller = Taller::factory()->create();
    $usuario = usuarioAsignadoAlTaller($otroTaller->id);

    expect(fn () => app(ActivarDesactivarUsuarioSistemaAction::class)->execute($usuario, $taller->id, false))
        ->toThrow(BusinessException::class);
});
