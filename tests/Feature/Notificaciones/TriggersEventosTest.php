<?php

use App\Actions\Inventario\RegistrarMovimientoInventarioAction;
use App\Actions\Pagos\RegistrarPagoAction;
use App\Actions\Resenas\GuardarResenaAction;
use App\Actions\Resenas\ModerarResenaAction;
use App\Actions\Roles\AsignarRolAction;
use App\Models\MetodoPago;
use App\Models\NotaVenta;
use App\Models\Notificacion;
use App\Models\Permiso;
use App\Models\Repuesto;
use App\Models\Rol;
use App\Models\Taller;
use App\Models\UnidadMedida;
use App\Models\UsuarioMarketplace;
use App\Models\UsuarioSistema;

/**
 * Cubre los 3 tipos de notificación que se enganchan a un evento de modelo ya existente
 * (`StockBajoDetectado`/`PagoRegistrado`/`ResenaGuardada`) — el evento se disparaba desde antes de
 * que `014-notificaciones` existiera (010/013/006), acá solo se verifica el listener nuevo.
 */
function usuarioConPermisoEnTaller(Taller $taller, string $permisoSlug): UsuarioSistema
{
    $usuario = UsuarioSistema::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null]);
    $rol->permisos()->attach(Permiso::factory()->create(['slug' => $permisoSlug]));

    app(AsignarRolAction::class)->execute($usuario, $rol, $taller->id, asignadoPor: null);

    return $usuario;
}

function usuarioConRolEnTaller(Taller $taller, string $rolSlug): UsuarioSistema
{
    $usuario = UsuarioSistema::factory()->create();
    $rol = Rol::factory()->create(['taller_id' => null, 'slug' => $rolSlug]);

    app(AsignarRolAction::class)->execute($usuario, $rol, $taller->id, asignadoPor: null);

    return $usuario;
}

it('stock.bajo notifica a los usuarios con permiso inventario.ver del taller', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $destinatario = usuarioConPermisoEnTaller($taller, 'inventario.ver');
    $repuesto = Repuesto::factory()->create([
        'taller_id' => $taller->id,
        'unidad_medida_id' => UnidadMedida::factory(),
        'stock_actual' => 10,
        'stock_minimo' => 5,
    ]);

    app(RegistrarMovimientoInventarioAction::class)->execute($repuesto, 'SALIDA', 6);

    expect(Notificacion::where('usuario_sistema_id', $destinatario->id)->where('tipo', 'stock.bajo')->exists())->toBeTrue();
});

it('stock.bajo no notifica si el movimiento no deja el stock por debajo del minimo', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    usuarioConPermisoEnTaller($taller, 'inventario.ver');
    $repuesto = Repuesto::factory()->create([
        'taller_id' => $taller->id,
        'unidad_medida_id' => UnidadMedida::factory(),
        'stock_actual' => 10,
        'stock_minimo' => 2,
    ]);

    app(RegistrarMovimientoInventarioAction::class)->execute($repuesto, 'SALIDA', 1);

    expect(Notificacion::where('tipo', 'stock.bajo')->exists())->toBeFalse();
});

it('pago.registrado notifica a los usuarios con permiso pagos.ver del taller', function () {
    $taller = Taller::factory()->create();
    session(['taller_activo_id' => $taller->id]);
    $destinatario = usuarioConPermisoEnTaller($taller, 'pagos.ver');
    $nota = NotaVenta::factory()->create(['taller_id' => $taller->id, 'subtotal' => 100, 'total' => 100, 'saldo' => 100]);
    $nota->lineas()->create([
        'servicio_catalogo_id' => null, 'repuesto_id' => null, 'descripcion' => 'Servicio',
        'cantidad' => 1, 'precio_unitario' => 100, 'descuento' => 0, 'subtotal' => 100,
    ]);
    $metodo = MetodoPago::factory()->create();

    app(RegistrarPagoAction::class)->execute($nota->fresh(), $metodo->id, 50);

    expect(Notificacion::where('usuario_sistema_id', $destinatario->id)->where('tipo', 'pago.registrado')->exists())->toBeTrue();
});

it('resena.nueva notifica a owner/shop-admin del taller al crear una reseña nueva', function () {
    $taller = Taller::factory()->create();
    $owner = usuarioConRolEnTaller($taller, 'owner');
    $marketplaceUser = UsuarioMarketplace::factory()->create();

    app(GuardarResenaAction::class)->execute($marketplaceUser, $taller, 5, 'Excelente');

    expect(Notificacion::where('usuario_sistema_id', $owner->id)->where('tipo', 'resena.nueva')->count())->toBe(1);
});

it('resena.nueva no vuelve a notificar cuando el mismo usuario edita su reseña', function () {
    $taller = Taller::factory()->create();
    $owner = usuarioConRolEnTaller($taller, 'owner');
    $marketplaceUser = UsuarioMarketplace::factory()->create();

    app(GuardarResenaAction::class)->execute($marketplaceUser, $taller, 5, 'Excelente');
    app(GuardarResenaAction::class)->execute($marketplaceUser, $taller, 4, 'Editada');

    expect(Notificacion::where('usuario_sistema_id', $owner->id)->where('tipo', 'resena.nueva')->count())->toBe(1);
});

it('resena.nueva no notifica cuando el super admin solo modera una reseña existente', function () {
    $taller = Taller::factory()->create();
    $owner = usuarioConRolEnTaller($taller, 'owner');
    $marketplaceUser = UsuarioMarketplace::factory()->create();
    $superAdmin = UsuarioSistema::factory()->create();
    $rolSuperAdmin = Rol::factory()->create(['taller_id' => null, 'slug' => 'super-admin']);
    $rolSuperAdmin->permisos()->attach(Permiso::factory()->create(['slug' => 'moderacion.resenas']));
    app(AsignarRolAction::class)->execute($superAdmin, $rolSuperAdmin, null, asignadoPor: null);

    $resena = app(GuardarResenaAction::class)->execute($marketplaceUser, $taller, 5, 'Excelente');
    Notificacion::query()->delete();

    // Re-fetch: en producción, moderar sucede en una request/sesión totalmente distinta a la
    // creación, así que `$resena` nunca sería el mismo objeto en memoria con `wasRecentlyCreated`
    // todavía en true — recrear esa condición acá evita un falso negativo del test.
    app(ModerarResenaAction::class)->execute($resena->fresh(), 'OCULTA', $superAdmin);

    expect(Notificacion::where('tipo', 'resena.nueva')->exists())->toBeFalse();
});
