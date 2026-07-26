<?php

namespace Database\Seeders;

use App\Models\Taller;
use Illuminate\Database\Seeder;

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

            if (! is_numeric($lat) || ! is_numeric($lon)) {
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

            // `opening_hours` (formato libre de OSM) ya no se guarda como texto suelto: el
            // esquema nuevo usa la tabla estructurada `talleres_horarios` (003-gestion-talleres).
            // Parsear ese formato queda fuera de alcance de este seeder de importación.
            Taller::create([
                'nombre' => $nombre,
                'direccion' => $direccion,
                'telefono' => $telefono,
                'lat' => (float) $lat,
                'lon' => (float) $lon,
                'osm_id' => $osmId,
                // `geom` se deriva automáticamente de lat/lon vía App\Traits\HasGeolocation.
            ]);

            $importados++;
        }

        $this->command->info('=== Resumen de importación ===');
        $this->command->info("Talleres importados: $importados");
        $this->command->info("Saltados (duplicados): $duplicados");
        $this->command->info("Saltados (inválidos): $inválidos");
        $this->command->info('Total procesados: '.count($features));
    }
}
