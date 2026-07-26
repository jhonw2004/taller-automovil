<?php

use App\Models\Favorito;
use App\Models\Resena;
use App\Models\Taller;
use App\Models\UsuarioMarketplace;

it('un visitante sin sesion no puede ver el dashboard', function () {
    $this->get('/dashboard')->assertRedirect();
});

it('muestra los favoritos y resenas del usuario autenticado', function () {
    $usuario = UsuarioMarketplace::factory()->create();
    $taller = Taller::factory()->create(['nombre' => 'Taller Favorito']);

    Favorito::factory()->create(['usuario_marketplace_id' => $usuario->id, 'taller_id' => $taller->id]);
    Resena::factory()->create(['usuario_marketplace_id' => $usuario->id, 'taller_id' => $taller->id, 'comentario' => 'Muy bueno']);

    $this->actingAs($usuario, 'web')
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Taller Favorito')
        ->assertSee('Muy bueno');
});

it('el dashboard de un usuario sin favoritos ni resenas muestra los estados vacios', function () {
    $usuario = UsuarioMarketplace::factory()->create();

    $this->actingAs($usuario, 'web')
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Sin favoritos todavía')
        ->assertSee('Aún no has escrito reseñas');
});
