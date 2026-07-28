<?php

use App\Models\Notificacion;
use App\Models\UsuarioMarketplace;
use App\Models\UsuarioSistema;

it('un visitante sin sesion no puede marcar una notificacion como leida', function () {
    $notificacion = Notificacion::factory()->create();

    $this->postJson("/notificaciones/{$notificacion->id}/marcar-leida")
        ->assertUnauthorized();
});

it('un usuario marketplace marca su propia notificacion como leida', function () {
    $marketplace = UsuarioMarketplace::factory()->create();
    $notificacion = Notificacion::factory()->paraMarketplace()->create(['usuario_marketplace_id' => $marketplace->id]);

    $this->actingAs($marketplace, 'web')
        ->postJson("/notificaciones/{$notificacion->id}/marcar-leida")
        ->assertOk();

    expect($notificacion->fresh()->leida)->toBeTrue();
});

it('un usuario sistema marca su propia notificacion como leida', function () {
    $sistema = UsuarioSistema::factory()->create();
    $notificacion = Notificacion::factory()->for($sistema, 'usuarioSistema')->create();

    $this->actingAs($sistema, 'sistema')
        ->postJson("/notificaciones/{$notificacion->id}/marcar-leida")
        ->assertOk();

    expect($notificacion->fresh()->leida)->toBeTrue();
});

it('rechaza marcar como leida una notificacion ajena', function () {
    $sistema = UsuarioSistema::factory()->create();
    $otro = UsuarioSistema::factory()->create();
    $notificacion = Notificacion::factory()->for($sistema, 'usuarioSistema')->create();

    $this->actingAs($otro, 'sistema')
        ->postJson("/notificaciones/{$notificacion->id}/marcar-leida")
        ->assertStatus(422);

    expect($notificacion->fresh()->leida)->toBeFalse();
});
