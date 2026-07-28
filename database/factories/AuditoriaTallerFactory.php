<?php

namespace Database\Factories;

use App\Models\AuditoriaTaller;
use App\Models\Taller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditoriaTaller>
 */
class AuditoriaTallerFactory extends Factory
{
    protected $model = AuditoriaTaller::class;

    public function definition(): array
    {
        return [
            'taller_id' => Taller::factory(),
            'usuario_sistema_id' => null,
            'operacion' => 'UPDATE',
            'datos_old' => null,
            'datos_new' => null,
            'lat_old' => null,
            'lat_new' => null,
            'lon_old' => null,
            'lon_new' => null,
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
