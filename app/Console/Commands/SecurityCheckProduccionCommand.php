<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * 020-seguridad-produccion §G: gate ejecutable antes de cada despliegue a producción. No
 * reemplaza revisión humana — es una red de seguridad barata contra el error más común y más
 * caro (olvidar apagar `APP_DEBUG`, dejar la cookie de sesión sin `Secure`, correr con el rol
 * de base de datos equivocado). Falla con código de salida distinto de cero si algo no cumple,
 * para poder usarse como paso bloqueante en un pipeline de despliegue.
 */
class SecurityCheckProduccionCommand extends Command
{
    protected $signature = 'security:check-produccion';

    protected $description = 'Verifica el checklist de seguridad de 020-seguridad-produccion antes de un despliegue.';

    public function handle(): int
    {
        $checks = [
            'APP_DEBUG=false' => config('app.debug') === false,
            'APP_ENV=production' => app()->environment('production'),
            'APP_URL usa https://' => str_starts_with((string) config('app.url'), 'https://'),
            'SESSION_SECURE_COOKIE=true' => config('session.secure') === true,
            'LOG_LEVEL distinto de debug' => config('logging.channels.single.level') !== 'debug',
            'DB_USERNAME no es root/postgres' => ! in_array(
                config('database.connections.pgsql.username'),
                ['root', 'postgres'],
                true
            ),
        ];

        $fallos = array_filter($checks, fn (bool $ok) => ! $ok);

        foreach ($checks as $descripcion => $ok) {
            $this->line(($ok ? '<fg=green>✓</>' : '<fg=red>✗</>')." {$descripcion}");
        }

        if ($fallos !== []) {
            $this->newLine();
            $this->error(count($fallos).' verificación(es) fallida(s). No desplegar hasta corregir.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Checklist de seguridad de producción: todo correcto.');

        return self::SUCCESS;
    }
}
