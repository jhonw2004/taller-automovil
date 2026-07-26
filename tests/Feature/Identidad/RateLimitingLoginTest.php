<?php

use App\Models\UsuarioSistema;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('bloquea el login tras 5 intentos fallidos en menos de un minuto (rate limiting nativo de Filament)', function () {
    $usuario = UsuarioSistema::factory()->create(['username' => 'ratelimit1']);

    Filament::setCurrentPanel(Filament::getPanel('erp'));

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('data.username', 'ratelimit1')
            ->set('data.password', 'incorrecta')
            ->call('authenticate');
    }

    // El 6to intento debe ser rechazado por el rate limiter antes de siquiera validar
    // credenciales — con password correcta, igual debe fallar por estar limitado.
    Livewire::test(Login::class)
        ->set('data.username', 'ratelimit1')
        ->set('data.password', 'Password123!')
        ->call('authenticate');

    expect(auth('sistema')->check())->toBeFalse();
});
