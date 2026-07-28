<?php

use App\Models\Notificacion;
use App\Models\UsuarioMarketplace;
use App\Models\UsuarioSistema;
use Illuminate\Database\QueryException;

it('rechaza una notificacion sin ningun destinatario a nivel de BD', function () {
    expect(fn () => Notificacion::create([
        'usuario_marketplace_id' => null,
        'usuario_sistema_id' => null,
        'tipo' => 'stock.bajo',
        'titulo' => 'Título',
        'mensaje' => 'Mensaje',
    ]))->toThrow(QueryException::class);
});

it('rechaza una notificacion con ambos destinatarios a nivel de BD', function () {
    $marketplace = UsuarioMarketplace::factory()->create();
    $sistema = UsuarioSistema::factory()->create();

    expect(fn () => Notificacion::create([
        'usuario_marketplace_id' => $marketplace->id,
        'usuario_sistema_id' => $sistema->id,
        'tipo' => 'stock.bajo',
        'titulo' => 'Título',
        'mensaje' => 'Mensaje',
    ]))->toThrow(QueryException::class);
});

it('permite una notificacion con exactamente un destinatario', function () {
    $sistema = UsuarioSistema::factory()->create();

    $notificacion = Notificacion::create([
        'usuario_marketplace_id' => null,
        'usuario_sistema_id' => $sistema->id,
        'tipo' => 'stock.bajo',
        'titulo' => 'Título',
        'mensaje' => 'Mensaje',
    ]);

    expect($notificacion->exists)->toBeTrue();
    expect($notificacion->fresh()->leida)->toBeFalse();
});

it('scopeNoLeidas filtra solo las no leidas', function () {
    $sistema = UsuarioSistema::factory()->create();
    Notificacion::factory()->for($sistema, 'usuarioSistema')->create();
    Notificacion::factory()->for($sistema, 'usuarioSistema')->leida()->create();

    expect(Notificacion::noLeidas()->count())->toBe(1);
});

it('marcarLeida() setea leida y leida_at, es idempotente', function () {
    $notificacion = Notificacion::factory()->create();

    $notificacion->marcarLeida();
    $primeraLeidaAt = $notificacion->leida_at;

    expect($notificacion->leida)->toBeTrue();
    expect($primeraLeidaAt)->not->toBeNull();

    $notificacion->marcarLeida();

    expect($notificacion->fresh()->leida_at->equalTo($primeraLeidaAt))->toBeTrue();
});
