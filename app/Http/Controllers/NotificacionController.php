<?php

namespace App\Http\Controllers;

use App\Actions\Notificaciones\MarcarNotificacionLeidaAction;
use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * `POST /notificaciones/{id}/marcar-leida` (014-spec.md/plan.md): único endpoint HTTP real de
 * esta feature — el bell del ERP (Livewire, dentro de Filament) llama a
 * `MarcarNotificacionLeidaAction` directamente sin pasar por HTTP, mismo criterio que el resto del
 * ERP nunca enruta sus propias interacciones (Resources/Actions son Livewire de por sí). Esta ruta
 * existe para el dashboard del marketplace (Blade+Alpine, sin Livewire) y acepta ambos guards
 * (`web`/`sistema`) para no duplicar el endpoint — la propiedad real la valida
 * `MarcarNotificacionLeidaAction`, no el guard.
 */
class NotificacionController extends Controller
{
    public function marcarLeida(Request $request, Notificacion $notificacion): RedirectResponse|JsonResponse
    {
        $actor = $request->user('web') ?? $request->user('sistema');

        if (! $actor) {
            abort(401);
        }

        app(MarcarNotificacionLeidaAction::class)->execute($notificacion, $actor);

        if ($request->wantsJson()) {
            return response()->json(['data' => ['id' => $notificacion->id, 'leida' => true]]);
        }

        return back();
    }
}
