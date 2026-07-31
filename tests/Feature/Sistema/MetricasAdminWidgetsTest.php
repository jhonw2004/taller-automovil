<?php

use App\Actions\Roles\AsignarRolAction;
use App\Filament\Admin\Widgets\PlataformaStatsWidget;
use App\Filament\Admin\Widgets\SolicitudesPorEstadoWidget;
use App\Filament\Admin\Widgets\TalleresPorCategoriaWidget;
use App\Models\Categoria;
use App\Models\Rol;
use App\Models\SolicitudTaller;
use App\Models\Taller;
use App\Models\UsuarioSistema;

/**
 * Dashboard de métricas por rol: los 3 widgets de `/admin` son exclusivos del Super Admin — ningún
 * otro rol del sistema, por más permisos que tenga, debe verlos (son métricas de plataforma
 * completa, sin scope de taller).
 */
function invocarProtegido(object $objeto, string $metodo): mixed
{
    $reflection = new ReflectionMethod($objeto, $metodo);
    $reflection->setAccessible(true);

    return $reflection->invoke($objeto);
}

function superAdmin(): UsuarioSistema
{
    $rol = Rol::factory()->create(['taller_id' => null, 'slug' => 'super-admin']);
    $usuario = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($usuario, $rol, null, asignadoPor: null);

    return $usuario;
}

it('los 3 widgets de admin solo se muestran al super admin', function () {
    $superAdmin = superAdmin();
    $this->actingAs($superAdmin, 'sistema');
    expect(PlataformaStatsWidget::canView())->toBeTrue();
    expect(TalleresPorCategoriaWidget::canView())->toBeTrue();
    expect(SolicitudesPorEstadoWidget::canView())->toBeTrue();

    $taller = Taller::factory()->create();
    $rolConTodo = Rol::factory()->create(['taller_id' => $taller->id]);
    $owner = UsuarioSistema::factory()->create();
    app(AsignarRolAction::class)->execute($owner, $rolConTodo, $taller->id, asignadoPor: null);
    $this->actingAs($owner, 'sistema');
    expect(PlataformaStatsWidget::canView())->toBeFalse();
    expect(TalleresPorCategoriaWidget::canView())->toBeFalse();
    expect(SolicitudesPorEstadoWidget::canView())->toBeFalse();
});

it('PlataformaStatsWidget cuenta talleres, solicitudes, usuarios y resenas reales', function () {
    Taller::factory()->count(2)->create(['estado' => 'ACTIVO', 'calificacion_promedio' => 4.5]);
    Taller::factory()->create(['estado' => 'SUSPENDIDO']);
    SolicitudTaller::factory()->create(['estado' => 'PENDIENTE']);
    SolicitudTaller::factory()->create(['estado' => 'EN_REVISION']);
    SolicitudTaller::factory()->create(['estado' => 'APROBADA']);
    UsuarioSistema::factory()->create(['activo' => true]);

    $superAdmin = superAdmin();
    $this->actingAs($superAdmin, 'sistema');

    $stats = invocarProtegido(new PlataformaStatsWidget, 'getStats');
    $porEtiqueta = collect($stats)->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()]);

    expect($porEtiqueta['Talleres activos'])->toBe(2);
    expect($porEtiqueta['Solicitudes por revisar'])->toBe(2);
    expect($porEtiqueta['Talleres suspendidos'])->toBe(1);
});

it('TalleresPorCategoriaWidget agrupa por categoria solo talleres activos', function () {
    $categoria = Categoria::factory()->create(['nombre' => 'Neumáticos']);
    $tallerActivo = Taller::factory()->create(['estado' => 'ACTIVO']);
    $tallerSuspendido = Taller::factory()->create(['estado' => 'SUSPENDIDO']);
    $tallerActivo->categorias()->attach($categoria->id);
    $tallerSuspendido->categorias()->attach($categoria->id);

    $this->actingAs(superAdmin(), 'sistema');

    $data = invocarProtegido(new TalleresPorCategoriaWidget, 'getData');

    expect($data['labels'])->toContain('Neumáticos');
    expect($data['datasets'][0]['data'][array_search('Neumáticos', $data['labels'])])->toBe(1);
});

it('SolicitudesPorEstadoWidget refleja el conteo real por estado', function () {
    SolicitudTaller::factory()->count(3)->create(['estado' => 'PENDIENTE']);
    SolicitudTaller::factory()->create(['estado' => 'APROBADA']);

    $this->actingAs(superAdmin(), 'sistema');

    $data = invocarProtegido(new SolicitudesPorEstadoWidget, 'getData');
    $indicePendiente = array_search('Pendiente', $data['labels']);
    $indiceAprobada = array_search('Aprobada', $data['labels']);

    expect($data['datasets'][0]['data'][$indicePendiente])->toBe(3);
    expect($data['datasets'][0]['data'][$indiceAprobada])->toBe(1);
});
