<?php

use App\Actions\Roles\AsignarRolAction;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

it('smoke: /admin dashboard renders super admin widgets', function () {
    $rol = Rol::factory()->create(['taller_id' => null, 'slug' => 'super-admin']);
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, null, asignadoPor: null);

    $response = $this->actingAs($usuario, 'sistema')->get('/admin');

    $response->assertSuccessful();
    $response->assertSee('Talleres activos');
    $response->assertSee('Talleres por categoría');
    $response->assertSee('Solicitudes de alta por estado');
});

it('smoke: /erp dashboard renders widgets for an owner', function () {
    $taller = Taller::factory()->create();
    $permisos = collect(['clientes.ver', 'ordenes.ver', 'notas.ver', 'inventario.ver', 'empleados.ver'])
        ->map(fn ($slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => $taller->id]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $taller->id, asignadoPor: null);

    $response = $this->actingAs($usuario, 'sistema')->get('/erp');

    $response->assertSuccessful();
    $response->assertSee('Empleados activos');
    $response->assertSee('Órdenes pendientes');
});
