<?php

use App\Models\AsignacionRol;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\UsuarioSistema;
use Illuminate\Database\QueryException;

it('la base de datos rechaza vigente_hasta anterior a vigente_desde', function () {
    $usuario = UsuarioSistema::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);

    expect(fn () => AsignacionRol::create([
        'usuario_sistema_id' => $usuario->id,
        'rol_id' => $rol->id,
        'taller_id' => null,
        'vigente_desde' => '2026-02-01',
        'vigente_hasta' => '2026-01-01',
    ]))->toThrow(QueryException::class);
});

it('tienePermiso ignora asignaciones cuya vigencia aún no comienza', function () {
    $usuario = UsuarioSistema::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);
    $permiso = Permiso::factory()->create(['slug' => 'ordenes.crear']);
    $rol->permisos()->attach($permiso->id);

    AsignacionRol::create([
        'usuario_sistema_id' => $usuario->id,
        'rol_id' => $rol->id,
        'taller_id' => null,
        'activo' => true,
        'vigente_desde' => now()->addDays(5)->toDateString(),
    ]);

    expect($usuario->tienePermiso('ordenes.crear'))->toBeFalse();
});

it('tienePermiso ignora asignaciones cuya vigencia ya terminó', function () {
    $usuario = UsuarioSistema::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);
    $permiso = Permiso::factory()->create(['slug' => 'ordenes.crear']);
    $rol->permisos()->attach($permiso->id);

    AsignacionRol::create([
        'usuario_sistema_id' => $usuario->id,
        'rol_id' => $rol->id,
        'taller_id' => null,
        'activo' => true,
        'vigente_desde' => now()->subDays(30)->toDateString(),
        'vigente_hasta' => now()->subDay()->toDateString(),
    ]);

    expect($usuario->tienePermiso('ordenes.crear'))->toBeFalse();
});

it('tienePermiso reconoce una asignación activa y vigente', function () {
    $usuario = UsuarioSistema::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);
    $permiso = Permiso::factory()->create(['slug' => 'ordenes.crear']);
    $rol->permisos()->attach($permiso->id);

    AsignacionRol::create([
        'usuario_sistema_id' => $usuario->id,
        'rol_id' => $rol->id,
        'taller_id' => null,
        'activo' => true,
        'vigente_desde' => now()->subDay()->toDateString(),
    ]);

    expect($usuario->tienePermiso('ordenes.crear'))->toBeTrue();
});
