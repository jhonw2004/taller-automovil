<?php

use App\Models\Resena;
use App\Models\Taller;
use App\Models\UsuarioMarketplace;
use App\Models\UsuarioSistema;

it('un visitante sin sesion no puede crear una resena', function () {
    $taller = Taller::factory()->create();

    $this->postJson('/api/resenas', ['taller_id' => $taller->id, 'calificacion' => 5])
        ->assertUnauthorized();
});

it('una sesion de guard sistema no puede usar las rutas de guard web', function () {
    $taller = Taller::factory()->create();
    $usuarioSistema = UsuarioSistema::factory()->create();

    $this->actingAs($usuarioSistema, 'sistema')
        ->postJson('/api/resenas', ['taller_id' => $taller->id, 'calificacion' => 5])
        ->assertUnauthorized();
});

it('crea una resena nueva y un segundo envio la actualiza (upsert, no duplica)', function () {
    $usuario = UsuarioMarketplace::factory()->create();
    $taller = Taller::factory()->create();

    $this->actingAs($usuario, 'web')
        ->postJson('/api/resenas', ['taller_id' => $taller->id, 'calificacion' => 3, 'comentario' => 'Bien'])
        ->assertOk();

    expect(Resena::query()->where('usuario_marketplace_id', $usuario->id)->where('taller_id', $taller->id)->count())->toBe(1);

    $this->actingAs($usuario, 'web')
        ->postJson('/api/resenas', ['taller_id' => $taller->id, 'calificacion' => 5, 'comentario' => 'Excelente'])
        ->assertOk();

    $resenas = Resena::query()->where('usuario_marketplace_id', $usuario->id)->where('taller_id', $taller->id)->get();

    expect($resenas)->toHaveCount(1)
        ->and($resenas->first()->calificacion)->toBe(5)
        ->and($resenas->first()->comentario)->toBe('Excelente');
});

it('rechaza calificaciones fuera de 1 a 5', function () {
    $usuario = UsuarioMarketplace::factory()->create();
    $taller = Taller::factory()->create();

    $this->actingAs($usuario, 'web')
        ->postJson('/api/resenas', ['taller_id' => $taller->id, 'calificacion' => 6])
        ->assertUnprocessable();
});

it('solo las resenas PUBLICADA cuentan para la calificacion del taller', function () {
    $taller = Taller::factory()->create();

    $autorPublicada = UsuarioMarketplace::factory()->create();
    $autorOculta = UsuarioMarketplace::factory()->create();

    $this->actingAs($autorPublicada, 'web')
        ->postJson('/api/resenas', ['taller_id' => $taller->id, 'calificacion' => 4])
        ->assertOk();

    Resena::factory()->oculta()->create([
        'usuario_marketplace_id' => $autorOculta->id,
        'taller_id' => $taller->id,
        'calificacion' => 1,
    ]);

    $taller->refresh();

    expect((float) $taller->calificacion_promedio)->toBe(4.0)
        ->and($taller->cantidad_resenas)->toBe(1);
});

it('recalcula la calificacion del taller de forma sincrona al eliminar una resena', function () {
    $taller = Taller::factory()->create();
    $usuario = UsuarioMarketplace::factory()->create();

    $this->actingAs($usuario, 'web')
        ->postJson('/api/resenas', ['taller_id' => $taller->id, 'calificacion' => 5])
        ->assertOk();

    $resena = Resena::query()->where('usuario_marketplace_id', $usuario->id)->firstOrFail();

    $taller->refresh();
    expect($taller->cantidad_resenas)->toBe(1);

    $this->actingAs($usuario, 'web')
        ->deleteJson("/api/resenas/{$resena->id}")
        ->assertOk();

    $taller->refresh();
    expect($taller->cantidad_resenas)->toBe(0)
        ->and((float) $taller->calificacion_promedio)->toBe(0.0);

    expect(Resena::query()->find($resena->id))->toBeNull()
        ->and(Resena::withTrashed()->find($resena->id))->not->toBeNull();
});

it('rechaza eliminar la resena de otro usuario', function () {
    $autor = UsuarioMarketplace::factory()->create();
    $otro = UsuarioMarketplace::factory()->create();
    $taller = Taller::factory()->create();

    $resena = Resena::factory()->create([
        'usuario_marketplace_id' => $autor->id,
        'taller_id' => $taller->id,
    ]);

    $this->actingAs($otro, 'web')
        ->deleteJson("/api/resenas/{$resena->id}")
        ->assertStatus(422);

    expect(Resena::query()->find($resena->id))->not->toBeNull();
});

it('aplica throttle:10,1 en la creacion de resenas', function () {
    $usuario = UsuarioMarketplace::factory()->create();
    $talleres = Taller::factory()->count(11)->create();

    $this->actingAs($usuario, 'web');

    foreach ($talleres->take(10) as $taller) {
        $this->postJson('/api/resenas', ['taller_id' => $taller->id, 'calificacion' => 5])->assertOk();
    }

    $this->postJson('/api/resenas', ['taller_id' => $talleres->last()->id, 'calificacion' => 5])
        ->assertStatus(429);
});
