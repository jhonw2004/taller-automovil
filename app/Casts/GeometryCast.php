<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/**
 * Castea una columna PostGIS `geometry(Point,4326)` desde/hacia un array ['lat' => float, 'lon' => float].
 *
 * PostgreSQL devuelve geometrías como EWKB hexadecimal. Este cast solo soporta el caso usado en
 * este proyecto: puntos little-endian con SRID embebido (el formato que produce ST_SetSRID/ST_MakePoint).
 */
class GeometryCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if (empty($value) || ! is_string($value)) {
            return null;
        }

        $binary = hex2bin($value);

        if ($binary === false || strlen($binary) < 21) {
            return null;
        }

        $byteOrder = unpack('C', $binary, 0)[1];
        $isLittleEndian = $byteOrder === 1;

        $type = unpack($isLittleEndian ? 'V' : 'N', $binary, 1)[1];
        $hasSrid = ($type & 0x20000000) !== 0;
        $offset = $hasSrid ? 9 : 5;

        $coords = unpack($isLittleEndian ? 'e2' : 'E2', $binary, $offset);

        return [
            'lon' => $coords[1],
            'lat' => $coords[2],
        ];
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): Expression
    {
        [$lat, $lon] = is_array($value)
            ? [(float) $value['lat'], (float) $value['lon']]
            : [(float) $value->lat, (float) $value->lon];

        return DB::raw(sprintf('ST_SetSRID(ST_MakePoint(%F, %F), 4326)', $lon, $lat));
    }
}
