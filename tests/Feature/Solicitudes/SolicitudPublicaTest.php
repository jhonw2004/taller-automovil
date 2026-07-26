<?php

use App\Models\SolicitudTaller;
use Illuminate\Support\Str;

it('la pagina publica de creacion carga sin autenticacion', function () {
    $this->get(route('solicitudes.create'))->assertSuccessful();
});

it('crea la solicitud desde el formulario publico y redirige al seguimiento por token', function () {
    $response = $this->post(route('solicitudes.store'), [
        'solicitante_nombre' => 'Ana Gómez',
        'solicitante_email' => 'ana@example.com',
        'solicitante_telefono' => '70011223',
        'taller_nombre' => 'Taller Ana',
    ]);

    $solicitud = SolicitudTaller::where('solicitante_email', 'ana@example.com')->firstOrFail();

    $response->assertRedirect(route('solicitudes.seguimiento', $solicitud->token_publico));
    expect($solicitud->estado)->toBe('PENDIENTE');
});

it('rechaza el formulario publico si faltan los minimos obligatorios', function () {
    $response = $this->post(route('solicitudes.store'), [
        'solicitante_nombre' => 'Ana Gómez',
    ]);

    $response->assertSessionHasErrors(['solicitante_email', 'solicitante_telefono', 'taller_nombre']);
    expect(SolicitudTaller::count())->toBe(0);
});

it('la pagina de seguimiento muestra el estado por token sin exponer el id', function () {
    $solicitud = SolicitudTaller::factory()->create();

    $this->get(route('solicitudes.seguimiento', $solicitud->token_publico))
        ->assertSuccessful()
        ->assertSee($solicitud->taller_nombre);
});

it('un token inexistente devuelve 404 en seguimiento', function () {
    $this->get(route('solicitudes.seguimiento', (string) Str::uuid()))
        ->assertNotFound();
});

it('el solicitante puede cancelar su propia solicitud PENDIENTE via el token', function () {
    $solicitud = SolicitudTaller::factory()->create(['estado' => 'PENDIENTE']);

    $this->post(route('solicitudes.cancelar', $solicitud->token_publico))
        ->assertRedirect(route('solicitudes.seguimiento', $solicitud->token_publico));

    expect($solicitud->fresh()->estado)->toBe('CANCELADA');
});

it('no se puede cancelar una solicitud ya COMPLETADA desde la pagina publica', function () {
    $solicitud = SolicitudTaller::factory()->completada()->create();

    $this->post(route('solicitudes.cancelar', $solicitud->token_publico))
        ->assertRedirect();

    expect($solicitud->fresh()->estado)->toBe('COMPLETADA');
});
