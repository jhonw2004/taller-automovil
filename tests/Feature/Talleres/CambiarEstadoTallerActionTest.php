<?php

use App\Actions\Roles\AsignarRolAction;
use App\Actions\Talleres\CambiarEstadoTallerAction;
use App\Exceptions\BusinessException;
use App\Models\AuditoriaEvento;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

it('el super admin puede suspender un taller', function () {
    $taller = Taller::factory()->create(['estado' => 'ACTIVO']);
    $superAdmin = UsuarioSistema::factory()->create();
    $rolSuperAdmin = Rol::factory()->create(['slug' => 'super-admin', 'taller_id' => null]);
    app(AsignarRolAction::class)->execute($superAdmin, $rolSuperAdmin, null);

    $resultado = app(CambiarEstadoTallerAction::class)->execute($taller, 'SUSPENDIDO', $superAdmin);

    expect($resultado->estado)->toBe('SUSPENDIDO');
    expect($taller->fresh()->estado)->toBe('SUSPENDIDO');
});

it('registra la auditoria del cambio de estado', function () {
    $taller = Taller::factory()->create(['estado' => 'ACTIVO']);
    $superAdmin = UsuarioSistema::factory()->create();
    $rolSuperAdmin = Rol::factory()->create(['slug' => 'super-admin', 'taller_id' => null]);
    app(AsignarRolAction::class)->execute($superAdmin, $rolSuperAdmin, null);

    app(CambiarEstadoTallerAction::class)->execute($taller, 'INACTIVO', $superAdmin);

    $log = AuditoriaEvento::where('evento', 'cambio_estado_taller')->latest('id')->first();
    expect($log)->not->toBeNull();
    expect($log->datos['anterior'])->toBe('ACTIVO');
    expect($log->datos['nuevo'])->toBe('INACTIVO');
});

it('rechaza el cambio de estado si el actor no es super admin', function () {
    $taller = Taller::factory()->create(['estado' => 'ACTIVO']);
    $noAdmin = UsuarioSistema::factory()->create();

    expect(fn () => app(CambiarEstadoTallerAction::class)->execute($taller, 'SUSPENDIDO', $noAdmin))
        ->toThrow(BusinessException::class);
});

it('rechaza un estado que no está en el catálogo permitido', function () {
    $taller = Taller::factory()->create(['estado' => 'ACTIVO']);
    $superAdmin = UsuarioSistema::factory()->create();
    $rolSuperAdmin = Rol::factory()->create(['slug' => 'super-admin', 'taller_id' => null]);
    app(AsignarRolAction::class)->execute($superAdmin, $rolSuperAdmin, null);

    expect(fn () => app(CambiarEstadoTallerAction::class)->execute($taller, 'BORRADO', $superAdmin))
        ->toThrow(BusinessException::class);
});
