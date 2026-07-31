<?php

use Illuminate\Database\Eloquent\Model;

/**
 * 020-seguridad-produccion §D. `$guarded = []` deshabilita por completo la protección de mass
 * assignment de Eloquent — cualquier clave del array/request pasada a `::create()`/`->fill()`
 * queda escribible, incluida una columna sensible que nunca debería llegar desde un formulario
 * (`activo`, `calificacion_promedio`, `taller_id` de otro tenant, etc.). Este test recorre todos
 * los modelos reales del proyecto (no una lista mantenida a mano, que se desactualiza) y confirma
 * que ninguno usa ese patrón — todos dependen de `$fillable` explícito (patrón ya documentado en
 * `constitution.md §1`).
 */
it('ningun modelo Eloquent usa $guarded = [] (mass assignment sin restriccion)', function () {
    $archivos = glob(app_path('Models/*.php'));

    expect($archivos)->not->toBeEmpty();

    $modelosConGuardedVacio = [];

    foreach ($archivos as $archivo) {
        $clase = 'App\\Models\\'.basename($archivo, '.php');

        if (! class_exists($clase)) {
            continue;
        }

        $reflexion = new ReflectionClass($clase);

        if ($reflexion->isAbstract() || ! $reflexion->isSubclassOf(Model::class)) {
            continue;
        }

        $instancia = $reflexion->newInstance();

        if ($instancia->getGuarded() === []) {
            $modelosConGuardedVacio[] = $clase;
        }
    }

    expect($modelosConGuardedVacio)->toBe([]);
});
