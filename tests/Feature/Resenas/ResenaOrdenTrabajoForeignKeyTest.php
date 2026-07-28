<?php

use App\Models\OrdenTrabajo;
use App\Models\Resena;
use App\Models\Taller;
use App\Models\UsuarioMarketplace;
use Illuminate\Database\QueryException;

/**
 * `resenas.orden_trabajo_id` se creó sin FK en 006 (011-ordenes-trabajo no existía todavía) y la
 * FK real se agregó en `2026_07_28_180001_add_orden_trabajo_foreign_to_resenas_table.php` una vez
 * que 011 ya existe. Este test cierra esa brecha documentada.
 */
it('rechaza un orden_trabajo_id que no existe', function () {
    expect(fn () => Resena::factory()->create(['orden_trabajo_id' => 999999]))
        ->toThrow(QueryException::class);
});

it('acepta un orden_trabajo_id real de una orden existente', function () {
    $taller = Taller::factory()->create();
    $orden = OrdenTrabajo::factory()->create(['taller_id' => $taller->id]);
    $usuario = UsuarioMarketplace::factory()->create();

    $resena = Resena::factory()->create([
        'usuario_marketplace_id' => $usuario->id,
        'taller_id' => $taller->id,
        'orden_trabajo_id' => $orden->id,
    ]);

    expect($resena->orden_trabajo_id)->toBe($orden->id);
});

it('permite orden_trabajo_id nulo (resena sin orden asociada)', function () {
    $resena = Resena::factory()->create(['orden_trabajo_id' => null]);

    expect($resena->orden_trabajo_id)->toBeNull();
});
