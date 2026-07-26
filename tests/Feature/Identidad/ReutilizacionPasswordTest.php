<?php

use App\Actions\Identidad\CambiarPasswordAction;
use App\Exceptions\BusinessException;
use App\Models\CredencialSistema;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\Hash;

it('rechaza reutilizar la contraseña actual', function () {
    $usuario = UsuarioSistema::factory()->create();
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create();

    (new CambiarPasswordAction)->execute($usuario, 'Password123!');
})->throws(BusinessException::class);

it('rechaza reutilizar una de las últimas 5 contraseñas', function () {
    $usuario = UsuarioSistema::factory()->create();
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create();

    $action = new CambiarPasswordAction;
    foreach (['Cambio1!Aa1', 'Cambio2!Aa1', 'Cambio3!Aa1'] as $p) {
        $action->execute($usuario, $p);
    }

    expect(fn () => $action->execute($usuario, 'Cambio2!Aa1'))
        ->toThrow(BusinessException::class);
});

it('permite reutilizar una contraseña más vieja que el límite de historial (5)', function () {
    // La factory de CredencialSistema usa 'Password123!' como password inicial (ver
    // database/factories/CredencialSistemaFactory.php).
    $passwordOriginal = 'Password123!';
    $usuario = UsuarioSistema::factory()->create();
    $credencial = CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create();

    $action = new CambiarPasswordAction;
    foreach (['Cambio1!Aa1', 'Cambio2!Aa1', 'Cambio3!Aa1', 'Cambio4!Aa1', 'Cambio5!Aa1', 'Cambio6!Aa1'] as $p) {
        $action->execute($usuario, $p);
    }

    // Tras 6 cambios, el historial recortado a 5 contiene Cambio1..Cambio5; la password
    // original ('Password123!') ya quedó fuera y debería poder reutilizarse.
    expect($usuario->historialPasswords()->count())->toBe(5);

    $action->execute($usuario, $passwordOriginal);

    $credencial->refresh();
    expect(Hash::check($passwordOriginal, $credencial->password_hash))->toBeTrue();
});

it('actualiza debe_cambiar_password, password_changed_at y password_expires_at al cambiar', function () {
    $usuario = UsuarioSistema::factory()->create();
    $credencial = CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create([
        'debe_cambiar_password' => true,
    ]);

    (new CambiarPasswordAction)->execute($usuario, 'NuevaSegura1!');

    $credencial->refresh();
    expect($credencial->debe_cambiar_password)->toBeFalse();
    expect($credencial->password_changed_at)->not->toBeNull();
    expect($credencial->password_expires_at->isFuture())->toBeTrue();
});
