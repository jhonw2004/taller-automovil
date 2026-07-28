<?php

use App\Actions\Notificaciones\CrearNotificacionAction;
use App\Actions\Notificaciones\MarcarNotificacionLeidaAction;
use App\Exceptions\BusinessException;
use App\Models\Notificacion;
use App\Models\UsuarioMarketplace;
use App\Models\UsuarioSistema;

it('CrearNotificacionAction rechaza sin ningun destinatario', function () {
    expect(fn () => app(CrearNotificacionAction::class)->execute(
        tipo: 'stock.bajo',
        titulo: 'Título',
        mensaje: 'Mensaje',
    ))->toThrow(BusinessException::class);
});

it('CrearNotificacionAction rechaza con ambos destinatarios', function () {
    $marketplace = UsuarioMarketplace::factory()->create();
    $sistema = UsuarioSistema::factory()->create();

    expect(fn () => app(CrearNotificacionAction::class)->execute(
        tipo: 'stock.bajo',
        titulo: 'Título',
        mensaje: 'Mensaje',
        usuarioMarketplaceId: $marketplace->id,
        usuarioSistemaId: $sistema->id,
    ))->toThrow(BusinessException::class);
});

it('CrearNotificacionAction crea una notificacion para un usuario sistema', function () {
    $sistema = UsuarioSistema::factory()->create();

    $notificacion = app(CrearNotificacionAction::class)->execute(
        tipo: 'usuario.creado',
        titulo: 'Título',
        mensaje: 'Mensaje',
        usuarioSistemaId: $sistema->id,
    );

    expect($notificacion->usuario_sistema_id)->toBe($sistema->id);
    expect($notificacion->usuario_marketplace_id)->toBeNull();
    expect($notificacion->leida)->toBeFalse();
});

it('CrearNotificacionAction crea una notificacion para un usuario marketplace', function () {
    $marketplace = UsuarioMarketplace::factory()->create();

    $notificacion = app(CrearNotificacionAction::class)->execute(
        tipo: 'resena.nueva',
        titulo: 'Título',
        mensaje: 'Mensaje',
        usuarioMarketplaceId: $marketplace->id,
    );

    expect($notificacion->usuario_marketplace_id)->toBe($marketplace->id);
});

it('MarcarNotificacionLeidaAction permite al usuario sistema destinatario marcarla', function () {
    $sistema = UsuarioSistema::factory()->create();
    $notificacion = Notificacion::factory()->for($sistema, 'usuarioSistema')->create();

    app(MarcarNotificacionLeidaAction::class)->execute($notificacion, $sistema);

    expect($notificacion->fresh()->leida)->toBeTrue();
});

it('MarcarNotificacionLeidaAction rechaza a un usuario sistema que no es el destinatario', function () {
    $sistema = UsuarioSistema::factory()->create();
    $otro = UsuarioSistema::factory()->create();
    $notificacion = Notificacion::factory()->for($sistema, 'usuarioSistema')->create();

    expect(fn () => app(MarcarNotificacionLeidaAction::class)->execute($notificacion, $otro))
        ->toThrow(BusinessException::class);

    expect($notificacion->fresh()->leida)->toBeFalse();
});

it('MarcarNotificacionLeidaAction permite al usuario marketplace destinatario marcarla', function () {
    $marketplace = UsuarioMarketplace::factory()->create();
    $notificacion = Notificacion::factory()->paraMarketplace()->create(['usuario_marketplace_id' => $marketplace->id]);

    app(MarcarNotificacionLeidaAction::class)->execute($notificacion, $marketplace);

    expect($notificacion->fresh()->leida)->toBeTrue();
});

it('MarcarNotificacionLeidaAction rechaza un usuario sistema marcando una notificacion de marketplace', function () {
    $marketplace = UsuarioMarketplace::factory()->create();
    $sistema = UsuarioSistema::factory()->create();
    $notificacion = Notificacion::factory()->paraMarketplace()->create(['usuario_marketplace_id' => $marketplace->id]);

    expect(fn () => app(MarcarNotificacionLeidaAction::class)->execute($notificacion, $sistema))
        ->toThrow(BusinessException::class);
});
