<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

class BelongsToTallerScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $table = $model->getTable();

        // Criterio de 003-gestion-talleres: un taller soft-deleteado oculta automáticamente
        // todas sus entidades hijas de las queries del ERP. Se usa NOT EXISTS correlacionado
        // (no whereNotIn) porque `NULL NOT IN (...)` evalúa NULL en SQL y excluiría también las
        // filas con `taller_id` NULL, que no deberían verse afectadas por esta condición.
        $builder->whereNotExists(function ($query) use ($table) {
            $query->select(DB::raw(1))
                ->from('talleres')
                ->whereColumn('talleres.id', "{$table}.taller_id")
                ->whereNotNull('talleres.deleted_at');
        });

        $tallerId = session('taller_activo_id');

        if ($tallerId) {
            $builder->where("{$table}.taller_id", $tallerId);
        }
    }
}
