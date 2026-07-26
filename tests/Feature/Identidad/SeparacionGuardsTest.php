<?php

use App\Models\UsuarioMarketplace;
use App\Models\UsuarioSistema;

it('un usuario marketplace autenticado no está autenticado en el guard sistema', function () {
    $usuario = UsuarioMarketplace::factory()->create();

    $this->actingAs($usuario, 'web');

    expect(auth('web')->check())->toBeTrue();
    expect(auth('sistema')->check())->toBeFalse();
});

it('un usuario sistema autenticado no está autenticado en el guard web', function () {
    $usuario = UsuarioSistema::factory()->create();

    $this->actingAs($usuario, 'sistema');

    expect(auth('sistema')->check())->toBeTrue();
    expect(auth('web')->check())->toBeFalse();
});

it('un usuario marketplace autenticado no puede acceder al panel /erp (guard sistema)', function () {
    $usuario = UsuarioMarketplace::factory()->create();

    $this->actingAs($usuario, 'web')
        ->get('/erp')
        ->assertRedirect('/erp/login');
});
