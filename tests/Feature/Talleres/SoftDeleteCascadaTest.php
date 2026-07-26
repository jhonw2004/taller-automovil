<?php

use App\Models\Rol;
use App\Models\Scopes\BelongsToTallerScope;
use App\Models\Taller;

/**
 * `BelongsToTallerScope` es infraestructura compartida (017) que van a usar las entidades hijas
 * de taller de specs futuras (007+, ninguna existe todavía). Se testea aquí, aislado, aplicando
 * el scope directamente contra la tabla `roles` (que ya tiene una columna `taller_id` real),
 * sin esperar a que exista un modelo que use el trait `BelongsToTaller`.
 */
it('excluye registros cuyo taller_id apunta a un taller soft-deleteado', function () {
    $tallerActivo = Taller::factory()->create();
    $tallerBorrado = Taller::factory()->create();
    $rolActivo = Rol::factory()->create(['taller_id' => $tallerActivo->id]);
    $rolDeTallerBorrado = Rol::factory()->create(['taller_id' => $tallerBorrado->id]);

    $tallerBorrado->delete();

    $builder = Rol::query();
    (new BelongsToTallerScope)->apply($builder, new Rol);
    $ids = $builder->pluck('id');

    expect($ids)->toContain($rolActivo->id)
        ->and($ids)->not->toContain($rolDeTallerBorrado->id);
});

it('restaurar el taller reactiva el acceso a sus entidades hijas automáticamente', function () {
    $taller = Taller::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => $taller->id]);

    $taller->delete();

    $builder = Rol::query();
    (new BelongsToTallerScope)->apply($builder, new Rol);
    expect($builder->pluck('id'))->not->toContain($rol->id);

    $taller->restore();

    $builder2 = Rol::query();
    (new BelongsToTallerScope)->apply($builder2, new Rol);
    expect($builder2->pluck('id'))->toContain($rol->id);
});

it('no excluye registros con taller_id NULL (roles/asignaciones globales)', function () {
    $rolGlobal = Rol::factory()->create(['taller_id' => null]);

    $builder = Rol::query();
    (new BelongsToTallerScope)->apply($builder, new Rol);

    expect($builder->pluck('id'))->toContain($rolGlobal->id);
});

it('el marketplace nunca muestra un taller con deleted_at, sin importar estado/visible_en_mapa', function () {
    $taller = Taller::factory()->visible()->create();
    $taller->delete();

    expect(Taller::visibleEnMarketplace()->find($taller->id))->toBeNull()
        ->and(Taller::withTrashed()->visibleEnMarketplace()->find($taller->id))->not->toBeNull();
});
