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
//
// Desactivar/activar opera sobre `asignaciones_rol.activo` (por taller), no sobre
// `UsuarioSistema.activo` (global) — un usuario desactivado en un taller sigue pudiendo
// autenticarse (el guard no lo rechaza, `UsuarioSistemaProvider` solo mira `activo` global),
// pero pierde acceso al panel `/erp` de ESE taller específico si era su único taller vigente.

it('ActivarDesactivarUsuarioSistemaAction: desactiva y el usuario pierde acceso al erp de ese taller', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioAsignadoAlTaller($taller->id, ['username' => 'empleado.activo']);
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create();

    $this->actingAs($usuario, 'sistema')->get('/erp')->assertSuccessful();

    app(ActivarDesactivarUsuarioSistemaAction::class)->execute($usuario, $taller->id, false);

    expect($usuario->fresh()->activo)->toBeTrue(); // el global no se toca
    $this->actingAs($usuario->fresh(), 'sistema')->get('/erp')->assertForbidden();
});

it('ActivarDesactivarUsuarioSistemaAction: reactiva y el usuario recupera acceso al erp de ese taller', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioAsignadoAlTaller($taller->id, ['username' => 'empleado.reactivado']);
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create();
    app(ActivarDesactivarUsuarioSistemaAction::class)->execute($usuario, $taller->id, false);

    $this->actingAs($usuario->fresh(), 'sistema')->get('/erp')->assertForbidden();

    app(ActivarDesactivarUsuarioSistemaAction::class)->execute($usuario, $taller->id, true);

    $this->actingAs($usuario->fresh(), 'sistema')->get('/erp')->assertSuccessful();
});

it('ActivarDesactivarUsuarioSistemaAction: desactivar en un taller no afecta el acceso a otro taller del mismo usuario', function () {
    $tallerA = Taller::factory()->create();
    $tallerB = Taller::factory()->create();
    $usuario = usuarioAsignadoAlTaller($tallerA->id, ['username' => 'empleado.multitaller']);
    $rolB = Rol::factory()->create(['taller_id' => null]);
    app(AsignarRolAction::class)->execute($usuario, $rolB, $tallerB->id, asignadoPor: null);
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create();

    app(ActivarDesactivarUsuarioSistemaAction::class)->execute($usuario, $tallerA->id, false);

    session(['taller_activo_id' => $tallerB->id]);
    $this->actingAs($usuario->fresh(), 'sistema')->get('/erp')->assertSuccessful();
    expect($usuario->fresh()->asignacionesVigentes()->pluck('taller_id')->all())->toBe([$tallerB->id]);
});

it('ActivarDesactivarUsuarioSistemaAction: rechaza si el usuario no pertenece al taller', function () {
    $taller = Taller::factory()->create();
    $otroTaller = Taller::factory()->create();
    $usuario = usuarioAsignadoAlTaller($otroTaller->id);

    expect(fn () => app(ActivarDesactivarUsuarioSistemaAction::class)->execute($usuario, $taller->id, false))
        ->toThrow(BusinessException::class);
});
