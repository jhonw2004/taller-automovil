<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasGeolocation
{
    public static function bootHasGeolocation(): void
    {
        static::saving(function ($model) {
            if ($model->lat !== null && $model->lon !== null) {
                $model->geom = DB::raw(sprintf(
                    'ST_SetSRID(ST_MakePoint(%F, %F), 4326)',
                    (float) $model->lon,
                    (float) $model->lat
                ));
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
