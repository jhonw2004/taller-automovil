<?php

namespace App\Http\Middleware;

use App\Actions\Auditoria\RegistrarAccesoAuditoriaAction;
use App\Models\UsuarioSistema;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Model;

/**
 * Extiende `Filament\Http\Middleware\Authenticate` (015-spec.md: "acceso denegado" es uno de los
 * 5 eventos de `auditoria_accesos`). Un usuario ya autenticado al que `canAccessPanel()` le niega
 * el panel (sin rol vigente en el taller, cuenta inactiva) nunca dispara `Login`/`Failed` — esos
 * eventos nativos solo cubren el intento de autenticación, no la autorización posterior de acceso
 * al panel — así que hay que interceptar el mismo punto donde Filament decide el `abort(403)` y
 * auditar antes de abortar, en vez de duplicar la lógica de `canAccessPanel()` en otro lugar.
 */
class AuditarAccesoDenegadoFilament extends Authenticate
{
    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            $this->unauthenticated($request, $guards);

            return;
        }

        $this->auth->shouldUse(Filament::getAuthGuard());

        /** @var Model $user */
        $user = $guard->user();

        $panel = Filament::getCurrentOrDefaultPanel();

        $autorizado = $user instanceof FilamentUser
            ? $user->canAccessPanel($panel)
            : config('app.env') === 'local';

        if (! $autorizado) {
            app(RegistrarAccesoAuditoriaAction::class)->execute(
                tipoAcceso: 'LOGIN',
                resultado: 'DENEGADO',
                usuarioSistemaId: $user instanceof UsuarioSistema ? $user->id : null,
                identificador: $user instanceof UsuarioSistema ? $user->username : null,
            );
        }

        abort_if(! $autorizado, 403);
    }
}
