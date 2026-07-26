<?php

use App\Models\Categoria;
use App\Models\Taller;
use App\Models\TallerHorario;

function crearTallerVisible(array $overrides = []): Taller
{
    return Taller::factory()->visible()->create(array_merge([
        'lat' => -17.7833,
        'lon' => -63.1821,
    ], $overrides));
}

it('nunca devuelve talleres inactivos, no visibles o borrados', function () {
    $visible = crearTallerVisible();
    $noVisible = Taller::factory()->create(['visible_en_mapa' => false]);
    $suspendido = Taller::factory()->visible()->suspendido()->create();
    $inactivo = Taller::factory()->visible()->inactivo()->create();
    $borrado = crearTallerVisible();
    $borrado->delete();

    $ids = collect($this->getJson('/api/talleres/search')->json('data'))->pluck('id');

    expect($ids)->toContain($visible->id)
        ->and($ids)->not->toContain($noVisible->id)
        ->and($ids)->not->toContain($suspendido->id)
        ->and($ids)->not->toContain($inactivo->id)
        ->and($ids)->not->toContain($borrado->id);
});

it('incluye todos los campos requeridos por el spec en cada resultado', function () {
    $categoria = Categoria::factory()->create();
    $taller = crearTallerVisible();
    $taller->categorias()->attach($categoria->id);

    $data = $this->getJson('/api/talleres/search')->json('data.0');

    expect($data)->toHaveKeys([
        'id', 'nombre', 'slug', 'descripcion_corta', 'lat', 'lon',
        'calificacion_promedio', 'cantidad_resenas', 'categorias', 'abierto_ahora', 'distancia_km',
    ])->and($data['categorias'])->toContain($categoria->nombre);
});

it('el filtro de radio con ST_DistanceSphere excluye talleres fuera del rango', function () {
    // Centro de Santa Cruz. ~1km de distancia (dentro de un radio de 5km).
    $cercano = crearTallerVisible(['lat' => -17.79, 'lon' => -63.19]);
    // La Paz, a cientos de km — fuera de cualquier radio razonable.
    $lejano = crearTallerVisible(['lat' => -16.5, 'lon' => -68.15]);

    $response = $this->getJson('/api/talleres/search?lat=-17.7833&lon=-63.1821&radio=5');

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toContain($cercano->id)
        ->and($ids)->not->toContain($lejano->id);

    $distancia = collect($response->json('data'))->firstWhere('id', $cercano->id)['distancia_km'];
    expect($distancia)->not->toBeNull();
});

it('filtra por categoria, texto, calificacion minima y combina resultados', function () {
    $categoria = Categoria::factory()->create();
    $conCategoria = crearTallerVisible(['nombre' => 'Frenos Express', 'calificacion_promedio' => 4.5]);
    $conCategoria->categorias()->attach($categoria->id);
    $sinCategoria = crearTallerVisible(['nombre' => 'Otro Taller', 'calificacion_promedio' => 2.0]);

    $porCategoria = collect($this->getJson("/api/talleres/search?categoria={$categoria->slug}")->json('data'))->pluck('id');
    expect($porCategoria)->toContain($conCategoria->id)->not->toContain($sinCategoria->id);

    $porTexto = collect($this->getJson('/api/talleres/search?q=Frenos')->json('data'))->pluck('id');
    expect($porTexto)->toContain($conCategoria->id)->not->toContain($sinCategoria->id);

    $porCalificacion = collect($this->getJson('/api/talleres/search?min_calificacion=4')->json('data'))->pluck('id');
    expect($porCalificacion)->toContain($conCategoria->id)->not->toContain($sinCategoria->id);
});

it('open_now solo incluye talleres abiertos en este momento', function () {
    $diaHoy = now('America/La_Paz')->isoWeekday();

    $abierto = crearTallerVisible();
    TallerHorario::factory()->create([
        'taller_id' => $abierto->id,
        'dia_semana' => $diaHoy,
        'hora_apertura' => '00:00',
        'hora_cierre' => '23:59',
        'cerrado' => false,
    ]);

    $cerrado = crearTallerVisible();
    TallerHorario::factory()->create([
        'taller_id' => $cerrado->id,
        'dia_semana' => $diaHoy,
        'cerrado' => true,
    ]);

    $ids = collect($this->getJson('/api/talleres/search?open_now=1')->json('data'))->pluck('id');

    expect($ids)->toContain($abierto->id)
        ->and($ids)->not->toContain($cerrado->id);
});

it('aplica throttle:30,1 y rechaza la solicitud numero 31 en el mismo minuto', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->getJson('/api/talleres/search')->assertOk();
    }

    $this->getJson('/api/talleres/search')->assertStatus(429);
});
