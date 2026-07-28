<?php

use App\Actions\Auditoria\RegistrarEventoAuditoriaAction;
use App\Exceptions\BusinessException;
use App\Models\AuditoriaEvento;
use App\Models\Taller;
use App\Models\UsuarioMarketplace;
use App\Models\UsuarioSistema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

it('escribe un evento con entidad y datos', function () {
    $taller = Taller::factory()->create();
    $usuario = UsuarioSistema::factory()->create();

    $evento = app(RegistrarEventoAuditoriaAction::class)->execute(
        evento: 'evento_de_prueba',
        usuarioSistemaId: $usuario->id,
        tallerId: $taller->id,
        entidad: $taller,
        datos: ['clave' => 'valor'],
    );

    expect($evento->evento)->toBe('evento_de_prueba');
    expect($evento->usuario_sistema_id)->toBe($usuario->id);
    expect($evento->taller_id)->toBe($taller->id);
    expect($evento->entidad_tipo)->toBe(Taller::class);
    expect($evento->entidad_id)->toBe($taller->id);
    expect($evento->datos)->toBe(['clave' => 'valor']);
});

it('permite un evento sin ningun actor (ej. proceso de sistema)', function () {
    $evento = app(RegistrarEventoAuditoriaAction::class)->execute(evento: 'evento_sistema');

    expect($evento->usuario_sistema_id)->toBeNull();
    expect($evento->usuario_marketplace_id)->toBeNull();
});

it('rechaza a nivel de BD que ambos actores esten no-nulos a la vez', function () {
    $usuarioSistema = UsuarioSistema::factory()->create();
    $usuarioMarketplace = UsuarioMarketplace::factory()->create();

    expect(fn () => AuditoriaEvento::create([
        'usuario_sistema_id' => $usuarioSistema->id,
        'usuario_marketplace_id' => $usuarioMarketplace->id,
        'evento' => 'invalido',
    ]))->toThrow(QueryException::class);
});

it('es append-only: update() y delete() lanzan BusinessException', function () {
    $evento = app(RegistrarEventoAuditoriaAction::class)->execute(evento: 'evento_de_prueba');

    expect(fn () => $evento->update(['evento' => 'otro']))->toThrow(BusinessException::class);
    expect(fn () => $evento->delete())->toThrow(BusinessException::class);

    expect(AuditoriaEvento::find($evento->id)->evento)->toBe('evento_de_prueba');
});

it('sin updated_at: la tabla no tiene esa columna', function () {
    expect(Schema::hasColumn('auditoria_eventos', 'updated_at'))->toBeFalse();
});
