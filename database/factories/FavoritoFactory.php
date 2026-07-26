<?php

namespace Database\Factories;

use App\Models\Favorito;
use App\Models\Taller;
use App\Models\UsuarioMarketplace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Favorito>
 */
class FavoritoFactory extends Factory
{
    protected $model = Favorito::class;

    public function definition(): array
    {
        return [
            'usuario_marketplace_id' => UsuarioMarketplace::factory(),
            'taller_id' => Taller::factory(),
        ];
    }
}
