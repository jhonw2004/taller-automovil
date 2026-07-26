<?php

use App\Models\Taller;

it('un taller nuevo arranca con calificacion_promedio y cantidad_resenas en 0', function () {
    $taller = Taller::factory()->create();

    // Postgres solo devuelve el PK en el INSERT; las columnas con DEFAULT a nivel de BD
    // (calificacion_promedio, cantidad_resenas) quedan en null en la instancia en memoria hasta
    // releerlas.
    $fresh = $taller->fresh();

    expect((float) $fresh->calificacion_promedio)->toBe(0.0)
        ->and($fresh->cantidad_resenas)->toBe(0);
});
