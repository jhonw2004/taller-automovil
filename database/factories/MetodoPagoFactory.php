<?php

namespace Database\Factories;

use App\Models\MetodoPago;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MetodoPago>
 */
class MetodoPagoFactory extends Factory
{
    protected $model = MetodoPago::class;

    public function definition(): array
    {
        $nombre = fake()->unique()->randomElement(['Efectivo', 'Tarjeta', 'QR', 'Transferencia', 'Cheque']);

        return [
            'nombre' => $nombre,
            'slug' => str($nombre)->slug(),
            'descripcion' => fake()->optional()->sentence(),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
