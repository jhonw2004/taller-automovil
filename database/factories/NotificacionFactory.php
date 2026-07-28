<?php

namespace Database\Factories;

use App\Models\Notificacion;
use App\Models\UsuarioMarketplace;
use App\Models\UsuarioSistema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notificacion>
 */
class NotificacionFactory extends Factory
{
    protected $model = Notificacion::class;

    public function definition(): array
    {
        return [
            'usuario_marketplace_id' => null,
            'usuario_sistema_id' => UsuarioSistema::factory(),
            'taller_id' => null,
            'orden_trabajo_id' => null,
            'tipo' => 'stock.bajo',
            'titulo' => fake()->sentence(4),
            'mensaje' => fake()->sentence(10),
            'data' => null,
            'leida' => false,
            'leida_at' => null,
        ];
    }

    public function paraMarketplace(): static
    {
        return $this->state(fn () => [
            'usuario_marketplace_id' => UsuarioMarketplace::factory(),
            'usuario_sistema_id' => null,
        ]);
    }

    public function leida(): static
    {
        return $this->state(fn () => ['leida' => true, 'leida_at' => now()]);
    }
}
