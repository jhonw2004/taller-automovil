<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;

class SequentialCodeGenerator
{
    /**
     * @param  string  $prefix  Ej: 'OT', 'NV'
     * @param  string  $table  Ej: 'ordenes_trabajo', 'notas_venta'
     * @param  int  $retries  Reintentos ante deadlock
     */
    public static function generate(
        string $tallerId,
        string $prefix,
        string $table,
        string $column = 'codigo',
        int $retries = 3
    ): string {
        $year = now()->format('Y');

        return DB::transaction(function () use ($tallerId, $prefix, $year, $table, $column) {
            $last = DB::table($table)
                ->where('taller_id', $tallerId)
                ->where($column, 'LIKE', "{$prefix}-{$year}-%")
                ->orderBy($column, 'desc')
                ->lockForUpdate()
                ->first();

            $nextNumber = $last
                ? (int) substr($last->$column, -3) + 1
                : 1;

            return sprintf('%s-%s-%03d', $prefix, $year, $nextNumber);
        }, $retries);
    }
}
