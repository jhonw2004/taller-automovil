<?php

use App\Models\Categoria;
use App\Models\Taller;

it('el home carga con el layout marketplace y contiene el hero, categorias y stats', function () {
    $categoria = Categoria::factory()->create(['activo' => true]);
    Taller::factory()->visible()->create();

    $this->get('/')
        ->assertOk()
        ->assertViewIs('marketplace.home')
        ->assertSee('Encuentra el taller mecánico ideal')
        ->assertSee($categoria->nombre)
        ->assertSee('Talleres publicados');
});

it('la pagina de busqueda carga con el layout marketplace', function () {
    $this->get('/talleres/buscar')
        ->assertOk()
        ->assertViewIs('marketplace.search.index');
});

it('los componentes Blade globales se renderizan sin errores con props minimas', function () {
    $badge = $this->blade('<x-badge type="success">Activo</x-badge>')->assertSee('Activo');
    $badge->assertSee('badge-success', false);

    $this->blade('<x-button variant="primary">Enviar</x-button>')
        ->assertSee('Enviar')
        ->assertSee('bg-obsidian', false);

    $this->blade('<x-card>Contenido</x-card>')
        ->assertSee('Contenido')
        ->assertSee('border-cloud', false)
        ->assertSee('rounded-cards', false);

    $this->blade('<x-empty-state title="Sin resultados" description="Prueba otro filtro" />')
        ->assertSee('Sin resultados')
        ->assertSee('Prueba otro filtro');

    $this->blade('<x-alert type="error">Algo salió mal</x-alert>')->assertSee('Algo salió mal');
});

it('los componentes Blade del marketplace se renderizan sin errores con props minimas', function () {
    $taller = Taller::factory()->visible()->create();

    $this->blade('<x-marketplace.workshop-card :taller="$taller" />', ['taller' => $taller])
        ->assertSee($taller->nombre);

    $this->blade('<x-marketplace.stats-block number="42" label="Talleres" />')
        ->assertSee('42')
        ->assertSee('Talleres');

    $this->blade('<x-marketplace.star-rating :value="4.5" />')->assertSee('svg', false);

    $this->blade(
        '<x-marketplace.review-card :resena="$resena" />',
        ['resena' => ['nombre' => 'Ana', 'calificacion' => 5, 'comentario' => 'Excelente atención']]
    )->assertSee('Ana')->assertSee('Excelente atención');
});
