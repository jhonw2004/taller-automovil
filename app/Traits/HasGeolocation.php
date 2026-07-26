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

    /**
     * `ST_DWithin` sobre `geometry` (no `geography`) mide en las unidades del SRID — en 4326 eso
     * es **grados**, no metros. Sin el cast a `geography` este filtro no filtra nada realista
     * (5000 "grados" cubre todo el planeta); verificado con dos puntos a 546km de distancia que
     * pasaban un radio de 5km sin el cast. `geography(...)` fuerza el cálculo esférico en metros.
     */
    public function scopeCercanoA(Builder $query, float $lat, float $lon, float $radioMetros): Builder
    {
        $table = $query->getModel()->getTable();

        return $query->whereRaw(
            "ST_DWithin(geography({$table}.geom), geography(ST_SetSRID(ST_MakePoint(?, ?), 4326)), ?)",
            [$lon, $lat, $radioMetros]
        );
    }

    /**
     * `selectRaw()` reemplaza el `SELECT *` implícito por la sola columna agregada si no hay
     * ningún `select` previo en el query — hay que agregar `{tabla}.*` explícitamente o el
     * modelo se hidrata solo con `distancia` y pierde el resto de sus columnas.
     */
    public function scopeConDistanciaA(Builder $query, float $lat, float $lon): Builder
    {
        $table = $query->getModel()->getTable();

        return $query->addSelect("{$table}.*")->selectRaw(
            "ST_DistanceSphere({$table}.geom, ST_SetSRID(ST_MakePoint(?, ?), 4326)) as distancia",
            [$lon, $lat]
        );
    }
}
