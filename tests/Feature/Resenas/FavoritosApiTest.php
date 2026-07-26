<?php

use App\Models\Favorito;
use App\Models\Taller;
use App\Models\UsuarioMarketplace;

it('un visitante sin sesion no puede marcar un favorito', function () {
    $taller = Taller::factory()->create();

    $this->postJson('/api/favoritos', ['taller_id' => $taller->id])
        ->assertUnauthorized();
});

it('marca un taller como favorito y un segundo intento no duplica (idempotente)', function () {
    $usuario = UsuarioMarketplace::factory()->create();
    $taller = Taller::factory()->create();

    $this->actingAs($usuario, 'web')
        ->postJson('/api/favoritos', ['taller_id' => $taller->id])
        ->assertOk();

    $this->actingAs($usuario, 'web')
        ->postJson('/api/favoritos', ['taller_id' => $taller->id])
        ->assertOk();

    expect(Favorito::query()->where('usuario_marketplace_id', $usuario->id)->where('taller_id', $taller->id)->count())->toBe(1);
});

it('elimina un favorito existente', function () {
    $usuario = UsuarioMarketplace::factory()->create();
    $taller = Taller::factory()->create();

    Favorito::factory()->create(['usuario_marketplace_id' => $usuario->id, 'taller_id' => $taller->id]);

    $this->actingAs($usuario, 'web')
        ->postJson('/api/favoritos/delete', ['taller_id' => $taller->id])
        ->assertOk();

    expect(Favorito::query()->where('usuario_marketplace_id', $usuario->id)->where('taller_id', $taller->id)->exists())->toBeFalse();
});

it('eliminar un favorito inexistente responde 200 (idempotente)', function () {
    $usuario = UsuarioMarketplace::factory()->create();
    $taller = Taller::factory()->create();

    $this->actingAs($usuario, 'web')
        ->postJson('/api/favoritos/delete', ['taller_id' => $taller->id])
        ->assertOk();
});

it('un usuario solo ve y elimina sus propios favoritos', function () {
    $usuario = UsuarioMarketplace::factory()->create();
    $otro = UsuarioMarketplace::factory()->create();
    $taller = Taller::factory()->create();

    $favoritoAjeno = Favorito::factory()->create(['usuario_marketplace_id' => $otro->id, 'taller_id' => $taller->id]);

    $this->actingAs($usuario, 'web')
        ->postJson('/api/favoritos/delete', ['taller_id' => $taller->id])
        ->assertOk();

    expect(Favorito::query()->find($favoritoAjeno->id))->not->toBeNull();
});
