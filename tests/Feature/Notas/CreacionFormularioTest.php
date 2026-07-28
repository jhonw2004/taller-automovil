<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Erp\Resources\NotaVentaResource\Pages\CreateNotaVenta;
use App\Models\Cliente;
use App\Models\NotaVenta;
use App\Models\OrdenTrabajo;
use App\Models\Permiso;
use App\Models\Repuesto;
use App\Models\Rol;
use App\Models\ServicioCatalogo;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('erp')));

function usuarioConPermisosDeNotasParaCrear(array $slugs, int $tallerId): UsuarioSistema
{
    $permisos = collect($slugs)->map(fn (string $slug) => Permiso::factory()->create(['slug' => $slug, 'activo' => true]));
    $rol = Rol::factory()->create(['taller_id' => $tallerId]);
    $rol->permisos()->attach($permisos->pluck('id'));
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, $tallerId, asignadoPor: null);

    return $usuario;
}

it('crea una nota de venta directa desde el formulario Filament con una linea de repuesto', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeNotasParaCrear(['notas.ver', 'notas.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $repuesto = Repuesto::factory()->create(['taller_id' => $taller->id, 'precio_venta' => 40, 'stock_actual' => 10]);

    Livewire::test(CreateNotaVenta::class)
        ->fillForm([
            'origen' => 'directa',
            'cliente_id' => $cliente->id,
            'lineas' => [
                ['tipo' => 'repuesto', 'repuesto_id' => $repuesto->id, 'cantidad' => 2, 'descuento' => 0],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $nota = NotaVenta::where('cliente_id', $cliente->id)->first();
    expect($nota)->not->toBeNull();
    expect($nota->taller_id)->toBe($taller->id);
    expect($nota->orden_trabajo_id)->toBeNull();
    expect($nota->estado)->toBe('EMITIDA');
    expect((float) $nota->total)->toBe(80.0);
    expect($nota->codigo)->toStartWith('NV-'.date('Y').'-');
    expect((float) $repuesto->fresh()->stock_actual)->toBe(8.0);
});

it('crea una nota de venta desde una orden completada, copiando sus lineas', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeNotasParaCrear(['notas.ver', 'notas.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $orden = OrdenTrabajo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id, 'estado' => 'COMPLETADA']);
    $servicio = ServicioCatalogo::factory()->create(['taller_id' => $taller->id]);
    $orden->lineasServicios()->create([
        'servicio_catalogo_id' => $servicio->id,
        'cantidad' => 1,
        'precio_unitario' => 70,
        'descuento' => 0,
        'subtotal' => 70,
        'estado' => 'REALIZADO',
    ]);

    Livewire::test(CreateNotaVenta::class)
        ->fillForm([
            'origen' => 'orden',
            'orden_trabajo_id' => $orden->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $nota = NotaVenta::where('orden_trabajo_id', $orden->id)->first();
    expect($nota)->not->toBeNull();
    expect($nota->cliente_id)->toBe($cliente->id);
    expect((float) $nota->total)->toBe(70.0);
    expect($nota->lineas)->toHaveCount(1);
});

it('rechaza generar una nota desde una orden que no esta completada (validado por la Action)', function () {
    $taller = Taller::factory()->create();
    $usuario = usuarioConPermisosDeNotasParaCrear(['notas.ver', 'notas.crear'], $taller->id);
    session(['taller_activo_id' => $taller->id]);
    $this->actingAs($usuario, 'sistema');

    $cliente = Cliente::factory()->create(['taller_id' => $taller->id]);
    $orden = OrdenTrabajo::factory()->create(['taller_id' => $taller->id, 'cliente_id' => $cliente->id, 'estado' => 'EN_PROGRESO']);

    Livewire::test(CreateNotaVenta::class)
        ->fillForm([
            'origen' => 'orden',
            'orden_trabajo_id' => $orden->id,
        ])
        ->call('create');

    expect(NotaVenta::count())->toBe(0);
});
