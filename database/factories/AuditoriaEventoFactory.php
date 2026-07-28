<?php

namespace Database\Factories;

use App\Models\AuditoriaEvento;
use App\Models\UsuarioSistema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditoriaEvento>
 */
class AuditoriaEventoFactory extends Factory
{
    protected $model = AuditoriaEvento::class;

    public function definition(): array
    {
        return [
            'usuario_marketplace_id' => null,
            'usuario_sistema_id' => UsuarioSistema::factory(),
            'taller_id' => null,
            'evento' => fake()->word(),
            'entidad_tipo' => null,
            'entidad_id' => null,
            'datos' => null,
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
