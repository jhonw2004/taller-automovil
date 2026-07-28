<?php

namespace App\Listeners\Auditoria;

use App\Actions\Auditoria\RegistrarAccesoAuditoriaAction;
use App\Models\UsuarioMarketplace;
use App\Models\UsuarioSistema;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * `auditoria_accesos` (015-plan.md) para LOGIN/LOGOUT/FAILED_LOGIN, ambos guards (`web` =
 * marketplace, `sistema` = ERP/Super Admin). El marketplace nunca dispara `Failed` en el flujo
 * normal (login exclusivo por Google OAuth, sin contraseña — mismo criterio ya documentado en
 * `RegistrarIntentoFallidoListener` de 001).
 *
 * "Acceso denegado" (LOGIN+DENEGADO) no nace de estos eventos nativos — un usuario ya autenticado
 * al que Filament le niega el panel (`canAccessPanel()` false) no dispara `Failed` ni `Login`, ver
 * `App\Http\Middleware\AuditarAccesoDenegadoFilament`. "Cambio de contraseña" tampoco pasa por
 * aquí: no existe un evento nativo `PasswordReset` aplicable (flujo self-service sin token de
 * email, ver docblock de `CambiarPasswordAction`) — esa Action audita directo.
 */
class RegistrarAccesoListener
{
    public function handleLogin(Login $event): void
    {
        app(RegistrarAccesoAuditoriaAction::class)->execute(
            tipoAcceso: 'LOGIN',
            resultado: 'EXITOSO',
            usuarioSistemaId: $event->user instanceof UsuarioSistema ? $event->user->id : null,
            usuarioMarketplaceId: $event->user instanceof UsuarioMarketplace ? $event->user->id : null,
            identificador: $this->identificador($event->user),
        );
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user === null) {
            return;
        }

        app(RegistrarAccesoAuditoriaAction::class)->execute(
            tipoAcceso: 'LOGOUT',
            resultado: 'EXITOSO',
            usuarioSistemaId: $event->user instanceof UsuarioSistema ? $event->user->id : null,
            usuarioMarketplaceId: $event->user instanceof UsuarioMarketplace ? $event->user->id : null,
            identificador: $this->identificador($event->user),
        );
    }

    public function handleFailed(Failed $event): void
    {
        if ($event->guard !== 'sistema') {
            return;
        }

        app(RegistrarAccesoAuditoriaAction::class)->execute(
            tipoAcceso: 'FAILED_LOGIN',
            resultado: 'FALLIDO',
            usuarioSistemaId: $event->user instanceof UsuarioSistema ? $event->user->id : null,
            identificador: $event->credentials['username'] ?? null,
        );
    }

    private function identificador(mixed $user): ?string
    {
        return match (true) {
            $user instanceof UsuarioSistema => $user->username,
            $user instanceof UsuarioMarketplace => $user->nombre,
            default => null,
        };
    }
}
