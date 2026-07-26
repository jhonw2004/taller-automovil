<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasGeolocation
{
    /**
     * Sincroniza `geom` desde `lat`/`lon` en cada save. Asigna un array (no un `DB::raw(...)`
     * directo): el modelo debe castear `geom` con `App\Casts\GeometryCast`, cuyo `set()` es quien
     * arma la expresión `ST_SetSRID(...)`. Asignar el `Expression` crudo aquí rompería el cast:
     * Eloquent intercepta la asignación por `isClassCastable()` y llamaría a
     * `GeometryCast::set()` con el `Expression` en vez del array `['lat'=>,'lon'=>]` que espera.
     */
    public static function bootHasGeolocation(): void
    {
        static::saving(function ($model) {
            if ($model->lat !== null && $model->lon !== null) {
                $model->geom = ['lat' => $model->lat, 'lon' => $model->lon];
            }
        });
    }

    public function scopeCercanoA(Builder $query, float $lat, float $lon, float $radioMetros): Builder
    {
        $table = $query->getModel()->getTable();

        return $query->whereRaw(
            "ST_DWithin({$table}.geom, ST_SetSRID(ST_MakePoint(?, ?), 4326), ?)",
            [$lon, $lat, $radioMetros]
        );
    }

    public function scopeConDistanciaA(Builder $query, float $lat, float $lon): Builder
    {
        $table = $query->getModel()->getTable();

        return $query->selectRaw(
            "ST_DistanceSphere({$table}.geom, ST_SetSRID(ST_MakePoint(?, ?), 4326)) as distancia",
            [$lon, $lat]
        );
    }
}
