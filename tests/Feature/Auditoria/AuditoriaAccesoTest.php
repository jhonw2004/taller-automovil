<?php

use App\Actions\Auditoria\RegistrarAccesoAuditoriaAction;
use App\Exceptions\BusinessException;
use App\Models\AuditoriaAcceso;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

it('escribe un acceso con identificador', function () {
    $acceso = app(RegistrarAccesoAuditoriaAction::class)->execute(
        tipoAcceso: 'LOGIN',
        resultado: 'EXITOSO',
        identificador: 'demo.usuario',
    );

    expect($acceso->tipo_acceso)->toBe('LOGIN');
    expect($acceso->resultado)->toBe('EXITOSO');
    expect($acceso->identificador)->toBe('demo.usuario');
});

it('rechaza a nivel de BD un tipo_acceso invalido', function () {
    expect(fn () => AuditoriaAcceso::create([
        'tipo_acceso' => 'INVALIDO',
        'resultado' => 'EXITOSO',
    ]))->toThrow(QueryException::class);
});

it('rechaza a nivel de BD un resultado invalido', function () {
    expect(fn () => AuditoriaAcceso::create([
        'tipo_acceso' => 'LOGIN',
        'resultado' => 'INVALIDO',
    ]))->toThrow(QueryException::class);
});

it('es append-only: update() y delete() lanzan BusinessException', function () {
    $acceso = app(RegistrarAccesoAuditoriaAction::class)->execute(tipoAcceso: 'LOGOUT', resultado: 'EXITOSO');

    expect(fn () => $acceso->update(['resultado' => 'FALLIDO']))->toThrow(BusinessException::class);
    expect(fn () => $acceso->delete())->toThrow(BusinessException::class);
});

it('nunca tiene una columna de password: la tabla no puede filtrar ni guardar el hash', function () {
    expect(Schema::hasColumn('auditoria_accesos', 'password'))->toBeFalse();
    expect(Schema::hasColumn('auditoria_accesos', 'password_hash'))->toBeFalse();
});
