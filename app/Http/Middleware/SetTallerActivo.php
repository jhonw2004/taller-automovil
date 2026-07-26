<?php

namespace App\Http\Middleware;

use App\Models\AsignacionRol;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Resuelve `session('taller_activo_id')` para el guard `sistema`. Solo se registra en el panel
 * `/erp` (spec 017): el Super Admin no opera "dentro" de un taller, su autorización ya se
 * resuelve con `esSuperAdmin()` en `UsuarioSistema::canAccessPanel()`.
 *
 * Usa `asignacionesVigentes()` (no `asignacionesRol()->where('activo', true)` a secas) porque
 * también valida `vigente_desde`/`vigente_hasta`, igual que `tienePermiso()`.
 *
 * No maneja el caso de cero talleres: `Filament\Http\Middleware\Authenticate` (registrado antes
 * en `authMiddleware()`) ya exige, vía `canAccessPanel()`, al menos una asignación vigente con
 * `taller_id` no nulo para llegar hasta aquí en /erp — la misma condición usada abajo. Si ese
 * middleware dejó pasar la request, `$tallerIds` nunca puede estar vacío en este punto.
 */
class SetTallerActivo
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('sistema')->user();

        if (! $user) {
            return $next($request);
        }

        $tallerIds = $user->asignacionesVigentes()
            ->filter(fn (AsignacionRol $asignacion) => $asignacion->taller_id !== null)
            ->pluck('taller_id')
            ->unique()
            ->values();

        $actual = $request->session()->get('taller_activo_id');

        if (! $tallerIds->contains($actual)) {
            // Sin sesión previa válida: se usa el primero (orden ascendente por id). Si el
            // usuario tiene más de un taller, el widget TenantSwitcher permite cambiarlo.
            $request->session()->put('taller_activo_id', $tallerIds->sort()->first());
        }

        return $next($request);
    }
}
