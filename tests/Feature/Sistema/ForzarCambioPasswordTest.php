<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Pages\CambiarPassword;
use App\Models\CredencialSistema;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UsuarioSistema;

it('redirige a la pagina de cambio de contraseña si debe_cambiar_password es true', function () {
    $taller = Taller::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);
    $usuario = UsuarioSistema::factory()->create();
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create(['debe_cambiar_password' => true]);
    app(AsignarRolAction::class)->execute($usuario, $rol, $taller->id, asignadoPor: null);

    $this->actingAs($usuario, 'sistema')
        ->get('/erp')
        ->assertRedirect(CambiarPassword::getUrl(panel: 'erp'));
});

it('redirige a la pagina de cambio de contraseña si la contraseña expiro', function () {
    $taller = Taller::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);
    $usuario = UsuarioSistema::factory()->create();
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create([
        'debe_cambiar_password' => false,
        'password_expires_at' => now()->subDay(),
    ]);
    app(AsignarRolAction::class)->execute($usuario, $rol, $taller->id, asignadoPor: null);

    $this->actingAs($usuario, 'sistema')
        ->get('/erp')
        ->assertRedirect(CambiarPassword::getUrl(panel: 'erp'));
});

it('no redirige si la contraseña esta al dia', function () {
    $taller = Taller::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);
    $usuario = UsuarioSistema::factory()->create();
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create([
        'debe_cambiar_password' => false,
        'password_expires_at' => now()->addDays(30),
    ]);
    app(AsignarRolAction::class)->execute($usuario, $rol, $taller->id, asignadoPor: null);

    $this->actingAs($usuario, 'sistema')
        ->get('/erp')
        ->assertSuccessful();
});

it('permite acceder a la propia pagina de cambio de contraseña sin redirigir en bucle', function () {
    $taller = Taller::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);
    $usuario = UsuarioSistema::factory()->create();
    CredencialSistema::factory()->for($usuario, 'usuarioSistema')->create(['debe_cambiar_password' => true]);
    app(AsignarRolAction::class)->execute($usuario, $rol, $taller->id, asignadoPor: null);

    $this->actingAs($usuario, 'sistema')
        ->get(CambiarPassword::getUrl(panel: 'erp'))
        ->assertSuccessful();
});
