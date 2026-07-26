<?php

use App\Models\Categoria;
use App\Models\Taller;

it('muestra el perfil publico de un taller activo y visible', function () {
    $categoria = Categoria::factory()->create();
    $taller = Taller::factory()->visible()->create(['nombre' => 'Taller Confiable']);
    $taller->categorias()->attach($categoria->id);

    $this->get("/talleres/{$taller->slug}")
        ->assertOk()
        ->assertSee('Taller Confiable')
        ->assertSee($categoria->nombre);
});

it('devuelve 404 para un taller inactivo', function () {
    $taller = Taller::factory()->visible()->inactivo()->create();

    $this->get("/talleres/{$taller->slug}")->assertNotFound();
});

it('devuelve 404 para un taller suspendido', function () {
    $taller = Taller::factory()->visible()->suspendido()->create();

    $this->get("/talleres/{$taller->slug}")->assertNotFound();
});

it('devuelve 404 para un taller no visible en el mapa', function () {
    $taller = Taller::factory()->create(['visible_en_mapa' => false]);

    $this->get("/talleres/{$taller->slug}")->assertNotFound();
});

it('devuelve 404 para un taller soft-deleteado', function () {
    $taller = Taller::factory()->visible()->create();
    $taller->delete();

    $this->get("/talleres/{$taller->slug}")->assertNotFound();
});

it('devuelve 404 para un slug inexistente', function () {
    $this->get('/talleres/no-existe-este-taller')->assertNotFound();
});

it('un visitante sin sesion puede ver el perfil pero no tiene acciones de favorito/resena', function () {
    $taller = Taller::factory()->visible()->create();

    $response = $this->get("/talleres/{$taller->slug}")->assertOk();

    // 006-resenas-favoritos no existe todavia: sin formulario de reseña ni botón de favorito.
    $response->assertDontSee('name="calificacion"', false);
});
