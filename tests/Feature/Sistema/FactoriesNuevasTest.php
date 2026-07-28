<?php

use App\Models\AsignacionRol;
use App\Models\AuditoriaAcceso;
use App\Models\AuditoriaEvento;
use App\Models\AuditoriaTaller;
use App\Models\HistorialPassword;
use App\Models\IdentidadOauth;

it('las 6 factories nuevas crean registros validos', function () {
    expect(AsignacionRol::factory()->create())->toBeInstanceOf(AsignacionRol::class)
        ->and(AuditoriaEvento::factory()->create())->toBeInstanceOf(AuditoriaEvento::class)
        ->and(AuditoriaAcceso::factory()->create())->toBeInstanceOf(AuditoriaAcceso::class)
        ->and(AuditoriaTaller::factory()->create())->toBeInstanceOf(AuditoriaTaller::class)
        ->and(HistorialPassword::factory()->create())->toBeInstanceOf(HistorialPassword::class)
        ->and(IdentidadOauth::factory()->create())->toBeInstanceOf(IdentidadOauth::class);
});
