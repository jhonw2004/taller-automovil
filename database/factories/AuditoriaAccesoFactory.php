<?php

namespace Database\Factories;

use App\Models\AuditoriaAcceso;
use App\Models\UsuarioSistema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditoriaAcceso>
 */
class AuditoriaAccesoFactory extends Factory
{
    protected $model = AuditoriaAcceso::class;

    public function definition(): array
    {
        return [
            'usuario_marketplace_id' => null,
            'usuario_sistema_id' => UsuarioSistema::factory(),
            'taller_id' => null,
            'tipo_acceso' => 'LOGIN',
            'resultado' => 'EXITOSO',
            'identificador' => fake()->userName(),
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
