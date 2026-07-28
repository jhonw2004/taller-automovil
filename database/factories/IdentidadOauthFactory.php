<?php

namespace Database\Factories;

use App\Models\IdentidadOauth;
use App\Models\UsuarioMarketplace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IdentidadOauth>
 */
class IdentidadOauthFactory extends Factory
{
    protected $model = IdentidadOauth::class;

    public function definition(): array
    {
        return [
            'usuario_marketplace_id' => UsuarioMarketplace::factory(),
            'provider' => 'GOOGLE',
            'provider_subject' => fake()->unique()->numerify('##################'),
            'email' => fake()->safeEmail(),
            'nombre' => fake()->name(),
            'avatar_url' => fake()->imageUrl(),
            'email_verified_at' => now(),
        ];
    }
}
