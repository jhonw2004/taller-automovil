<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * La sesión del guard `sistema` expira a los 30 minutos de inactividad (spec 001).
 *
 * `config('session.lifetime')` es un único valor global compartido con el guard `web`
 * (7 días, sesión persistente de marketplace), así que la ventana más estricta de
 * `sistema` se controla aquí con un timestamp propio en sesión, no con SESSION_LIFETIME.
 */
class CheckSessionExpiration
{
    private const MINUTOS_INACTIVIDAD = 30;

    public function handle(Request $request, Closure $next)
    {
        if (! Auth::guard('sistema')->check()) {
            return $next($request);
        }

        $ultimaActividad = $request->session()->get('sistema_last_activity');

        // No usar diffInMinutes()->'> N': en Carbon 3.x devuelve un valor con signo (negativo
        // si $ultimaActividad es pasado), no el valor absoluto que versiones anteriores daban
        // por defecto — comparar fechas directamente evita esa ambigüedad.
        if ($ultimaActividad && $ultimaActividad->lt(now()->subMinutes(self::MINUTOS_INACTIVIDAD))) {
            Auth::guard('sistema')->logout();
            $request->session()->regenerate();

            return redirect(Filament::getPanel('erp')->getLoginUrl())
                ->with('error', 'Sesión expirada por inactividad');
        }

        $request->session()->put('sistema_last_activity', now());

        return $next($request);
    }
}
