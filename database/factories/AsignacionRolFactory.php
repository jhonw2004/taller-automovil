<?php

namespace Database\Factories;

use App\Models\AsignacionRol;
use App\Models\Rol;
use App\Models\UsuarioSistema;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AsignacionRol>
 */
class AsignacionRolFactory extends Factory
{
    protected $model = AsignacionRol::class;

    public function definition(): array
    {
        return [
            'usuario_sistema_id' => UsuarioSistema::factory(),
            'rol_id' => Rol::factory(),
            'taller_id' => null,
            'activo' => true,
            'asignado_por_usuario_sistema_id' => null,
            'vigente_desde' => now()->toDateString(),
            'vigente_hasta' => null,
        ];
    }
}
