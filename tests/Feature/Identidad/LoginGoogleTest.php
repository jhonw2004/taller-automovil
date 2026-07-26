<?php

use App\Models\Identidad;
use App\Models\IdentidadOauth;
use App\Models\UsuarioMarketplace;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function googleUserStub(string $subject, string $email = 'juan@gmail.com'): SocialiteUser
{
    $user = new SocialiteUser;
    $user->id = $subject;
    $user->email = $email;
    $user->name = 'Juan Perez';
    $user->avatar = 'https://example.com/avatar.jpg';

    return $user;
}

it('crea identidad y usuario marketplace en el primer login con Google', function () {
    $googleUser = googleUserStub('google-111');
    Socialite::shouldReceive('driver->user')->andReturn($googleUser);

    $this->get('/auth/google/callback')->assertRedirect('/');

    expect(Identidad::where('tipo', 'MARKETPLACE')->count())->toBe(1);
    expect(UsuarioMarketplace::count())->toBe(1);
    expect(IdentidadOauth::where('provider_subject', 'google-111')->count())->toBe(1);
    $this->assertAuthenticated('web');
});

it('reutiliza la misma identidad en logins repetidos sin duplicar registros (idempotente)', function () {
    $googleUser = googleUserStub('google-222');
    Socialite::shouldReceive('driver->user')->andReturn($googleUser);

    $this->get('/auth/google/callback');
    auth('web')->logout();

    $this->get('/auth/google/callback');

    expect(Identidad::where('tipo', 'MARKETPLACE')->count())->toBe(1);
    expect(UsuarioMarketplace::count())->toBe(1);
    expect(IdentidadOauth::count())->toBe(1);
});
