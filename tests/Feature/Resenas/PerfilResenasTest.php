<?php

use App\Models\Favorito;
use App\Models\Resena;
use App\Models\Taller;
use App\Models\UsuarioMarketplace;

it('un usuario autenticado sin resena previa ve el formulario para escribir una', function () {
    $usuario = UsuarioMarketplace::factory()->create();
    $taller = Taller::factory()->visible()->create();

    $this->actingAs($usuario, 'web')
        ->get("/talleres/{$taller->slug}")
        ->assertOk()
        ->assertSee('Escribir reseña')
        ->assertSee('name="calificacion"', false);
});

it('un usuario autenticado con resena previa ve "Mi reseña" y las de otros usuarios', function () {
    $autor = UsuarioMarketplace::factory()->create();
    $otro = UsuarioMarketplace::factory()->create(['nombre' => 'Otro Cliente']);
    $taller = Taller::factory()->visible()->create();

    Resena::factory()->create([
        'usuario_marketplace_id' => $autor->id,
        'taller_id' => $taller->id,
        'comentario' => 'Mi propia opinion',
    ]);
    Resena::factory()->create([
        'usuario_marketplace_id' => $otro->id,
        'taller_id' => $taller->id,
        'comentario' => 'Opinion de otro cliente',
    ]);

    $this->actingAs($autor, 'web')
        ->get("/talleres/{$taller->slug}")
        ->assertOk()
        ->assertSee('Mi reseña')
        ->assertSee('Opinion de otro cliente')
        ->assertSee('Otro Cliente');
});

it('una resena oculta por moderacion no aparece en el perfil publico', function () {
    $taller = Taller::factory()->visible()->create();

    Resena::factory()->oculta()->create(['taller_id' => $taller->id, 'comentario' => 'Comentario oculto']);

    $this->get("/talleres/{$taller->slug}")
        ->assertOk()
        ->assertDontSee('Comentario oculto');
});

it('el boton de favorito refleja si el taller ya es favorito del usuario', function () {
    $usuario = UsuarioMarketplace::factory()->create();
    $taller = Taller::factory()->visible()->create();

    Favorito::factory()->create(['usuario_marketplace_id' => $usuario->id, 'taller_id' => $taller->id]);

    $this->actingAs($usuario, 'web')
        ->get("/talleres/{$taller->slug}")
        ->assertOk()
        ->assertSee('favoritoToggle('.$taller->id.', true)', false);
});
