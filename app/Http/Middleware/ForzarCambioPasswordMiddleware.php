<?php

namespace App\Http\Middleware;

use App\Filament\Admin\Pages\CambiarPassword as CambiarPasswordAdmin;
use App\Filament\Erp\Pages\CambiarPassword as CambiarPasswordErp;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Bloquea la navegación de un usuario sistema con `debe_cambiar_password=true` o contraseña
 * expirada (spec 001, pendiente hasta esta sesión) hasta que cambie la contraseña. Se registra
 * en ambos paneles (un Super Admin recién creado también tiene `debe_cambiar_password=true`),
 * con el id del panel como parámetro de ruta (`ForzarCambioPasswordMiddleware::class.':erp'`)
 * en vez de resolver el panel actual dinámicamente.
 */
class ForzarCambioPasswordMiddleware
{
    public function handle(Request $request, Closure $next, string $panelId)
    {
        $user = Auth::guard('sistema')->user();

        if (! $user) {
            return $next($request);
        }

        $credencial = $user->credencialSistema;

        $debeCambiar = $credencial
            && ($credencial->debe_cambiar_password
                || ($credencial->password_expires_at && $credencial->password_expires_at->isPast()));

        if (! $debeCambiar) {
            return $next($request);
        }

        $paginaCambio = $panelId === 'admin' ? CambiarPasswordAdmin::class : CambiarPasswordErp::class;
        // `panel: $panelId` explícito: sin esto, `getUrl()` resuelve contra
        // `Filament::getCurrentOrDefaultPanel()`, que fuera de una request ya enrutada por ese
        // panel cae al panel `->default()` (admin) — ambas páginas comparten el mismo slug
        // relativo ("cambiar-password"), así que resolvía siempre la URL de /admin.
        $urlCambio = $paginaCambio::getUrl(panel: $panelId);
        $urlLogout = Filament::getPanel($panelId)->getLogoutUrl();

        if (in_array($request->url(), [$urlCambio, $urlLogout], true)) {
            return $next($request);
        }

        return redirect($urlCambio);
    }
}
