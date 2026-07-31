<?php

use App\Http\Middleware\CheckSessionExpiration;
use App\Models\CredencialSistema;
use App\Models\UsuarioSistema;
use Illuminate\Http\Request;

/**
 * Se prueba el middleware directamente (sin pasar por toda la pila HTTP de Filament) porque
 * simular la sesión de un request completo vía withSession()/actingAs() choca con
 * `StartSession` regenerando el ID de sesión al no haber un cookie real de por medio en el
 * cliente de test. Aislar `CheckSessionExpiration::handle()` prueba exactamente la lógica que
 * escribimos, sin la incertidumbre de la pila de middleware de Filament alrededor.
 *
 * Reutiliza la sesión ya vinculada al contenedor (`app('session.store')`) en vez de crear una
 * `Store` aislada: `redirect()->with(...)` flashea contra esa instancia del contenedor, no
 * contra la que se le adjunte manualmente al `Request`.
 */
function requestConSesion(UsuarioSistema $usuario, array $sessionData = []): Request
{
    auth('sistema')->login($usuario);

    $session = app('session.store');
    if (! $session->isStarted()) {
        $session->start();
    }
    foreach ($sessionData as $key => $value) {
        $session->put($key, $value);
    }

    $request = Request::create('/admin');
    $request->setLaravelSession($session);

    return $request;
}

it('deja pasar la request y actualiza sistema_last_activity si la inactividad es menor a 30 min', function () {
    $usuario = UsuarioSistema::factory()->create();
    $request = requestConSesion($usuario, ['sistema_last_activity' => now()->subMinutes(20)]);

    $siguienteLlamado = false;
    $response = (new CheckSessionExpiration)->handle($request, function ($req) use (&$siguienteLlamado) {
        $siguienteLlamado = true;

        return response('ok');
    });

    expect($siguienteLlamado)->toBeTrue();
    expect($response->getContent())->toBe('ok');
    expect(auth('sistema')->check())->toBeTrue();
    expect($request->session()->get('sistema_last_activity')->diffInSeconds(now()))->toBeLessThan(2);
});

it('cierra sesión y redirige al login si la inactividad supera los 30 min', function () {
    $usuario = UsuarioSistema::factory()->create();
    $request = requestConSesion($usuario, ['sistema_last_activity' => now()->subMinutes(31)]);

    $siguienteLlamado = false;
    $response = (new CheckSessionExpiration)->handle($request, function () use (&$siguienteLlamado) {
        $siguienteLlamado = true;

        return response('no deberia llegar aca');
    });

    expect($siguienteLlamado)->toBeFalse();
    expect($response->isRedirect())->toBeTrue();
    expect(auth('sistema')->check())->toBeFalse();
    expect($request->session()->get('error'))->toBe('Sesión expirada por inactividad');
});

/**
 * Bug real corregido 2026-07-31: el middleware redirigía siempre a `/erp/login` (hardcodeado),
 * sin importar en qué panel expiraba la sesión — el Super Admin, cuya sesión vive en `/admin`,
 * terminaba en el login del panel equivocado. Sin `Filament::setCurrentPanel()` en este test
 * aislado (no pasa por `SetUpPanel`), `getCurrentOrDefaultPanel()` cae al panel `->default()`,
 * que es `admin` (ver `AdminPanelProvider`) — el destino correcto ya no es `erp` a ciegas.
 */
it('redirige al login del panel actual (no siempre a /erp/login) al expirar por inactividad', function () {
    $usuario = UsuarioSistema::factory()->create();
    $request = requestConSesion($usuario, ['sistema_last_activity' => now()->subMinutes(31)]);

    $response = (new CheckSessionExpiration)->handle($request, fn () => response('no deberia llegar aca'));

    expect($response->headers->get('Location'))->toContain('/admin/login');
    expect($response->headers->get('Location'))->not->toContain('/erp/login');
});

/**
 * Bug real corregido 2026-07-31: `sistema_last_activity` solo se escribía dentro de
 * `CheckSessionExpiration`, nunca al loguearse — un valor viejo (de una sesión previa ya
 * expirada) sobrevivía a `session()->regenerate()` en el login y la siguiente request
 * autenticada se trataba como "expirada por inactividad" inmediatamente después de un login
 * exitoso. `RegistrarAccesoListener::handleLogin()` ahora resetea el timestamp para el guard
 * `sistema`.
 */
it('resetea sistema_last_activity al loguearse, incluso si la sesión trae un valor viejo', function () {
    $usuario = UsuarioSistema::factory()->create(['username' => 'reseteo.actividad']);
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create();

    session()->put('sistema_last_activity', now()->subHours(2));

    $exito = auth('sistema')->attempt(['username' => 'reseteo.actividad', 'password' => 'Password123!']);

    expect($exito)->toBeTrue();
    expect(session()->get('sistema_last_activity')->diffInSeconds(now()))->toBeLessThan(2);
});

it('deja pasar la request sin tocar la sesión si no hay usuario autenticado en el guard sistema', function () {
    $request = Request::create('/admin');
    $session = app('session.store');
    if (! $session->isStarted()) {
        $session->start();
    }
    $request->setLaravelSession($session);

    $siguienteLlamado = false;
    (new CheckSessionExpiration)->handle($request, function () use (&$siguienteLlamado) {
        $siguienteLlamado = true;

        return response('ok');
    });

    expect($siguienteLlamado)->toBeTrue();
    expect($request->session()->has('sistema_last_activity'))->toBeFalse();
});
