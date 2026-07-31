<?php

it('falla con codigo de salida distinto de cero si la config no es segura', function () {
    config(['app.debug' => true]);

    $this->artisan('security:check-produccion')
        ->assertExitCode(1);
});

it('pasa con codigo de salida 0 si toda la config de produccion es correcta', function () {
    config([
        'app.debug' => false,
        'app.env' => 'production',
        'app.url' => 'https://tallerpro.example.com',
        'session.secure' => true,
        'logging.channels.single.level' => 'warning',
        'database.connections.pgsql.username' => 'taller_app',
    ]);
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('security:check-produccion')
        ->assertExitCode(0);
});

it('falla si DB_USERNAME es root o postgres', function () {
    config([
        'app.debug' => false,
        'app.env' => 'production',
        'app.url' => 'https://tallerpro.example.com',
        'session.secure' => true,
        'logging.channels.single.level' => 'warning',
        'database.connections.pgsql.username' => 'postgres',
    ]);
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('security:check-produccion')
        ->assertExitCode(1);
});
