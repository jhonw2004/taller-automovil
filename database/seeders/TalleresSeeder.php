<?php

namespace Database\Seeders;

use App\Models\Taller;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TalleresSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('storage/app/taller_automoviles.geojson');
        $json = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $features = $json['features'] ?? [];
        $importados = 0;
        $duplicados = 0;
        $inválidos = 0;

        foreach ($features as $feature) {
            $props = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? [];

            if (($geometry['type'] ?? '') !== 'Point') {
                $inválidos++;
                continue;
            }

            [$lon, $lat] = $geometry['coordinates'];

            if (!is_numeric($lat) || !is_numeric($lon)) {
                $inválidos++;
                continue;
            }

            $osmId = $props['@id'] ?? null;

            if ($osmId && Taller::where('osm_id', $osmId)->exists()) {
                $duplicados++;
                continue;
            }

            $nombre = $props['name'] ?? 'Taller sin nombre';

            $direccion = null;
            $calle = $props['addr:street'] ?? null;
            $numero = $props['addr:housenumber'] ?? null;
            if ($calle && $numero) {
                $direccion = "$calle $numero";
            } elseif ($calle) {
                $direccion = $calle;
            }

            $telefono = $props['phone']
                ?? $props['contact:phone']
                ?? $props['contact:mobile']
                ?? null;

            $horario = $props['opening_hours'] ?? null;

            Taller::create([
                'nombre' => $nombre,
                'direccion' => $direccion,
                'telefono' => $telefono,
                'horario' => $horario,
                'lat' => (float) $lat,
                'lon' => (float) $lon,
                'osm_id' => $osmId,
                'geom' => DB::raw("ST_SetSRID(ST_MakePoint($lon, $lat), 4326)"),
            ]);

            $importados++;
        }

        $this->command->info("=== Resumen de importación ===");
        $this->command->info("Talleres importados: $importados");
        $this->command->info("Saltados (duplicados): $duplicados");
        $this->command->info("Saltados (inválidos): $inválidos");
        $this->command->info("Total procesados: " . count($features));
    }
}
