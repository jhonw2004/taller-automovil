<?php

namespace App\Http\Controllers;

use App\Actions\Solicitudes\CancelarSolicitudAction;
use App\Actions\Solicitudes\CrearSolicitudTallerAction;
use App\Exceptions\BusinessException;
use App\Http\Requests\Solicitudes\CrearSolicitudTallerRequest;
use App\Models\Categoria;
use App\Models\SolicitudTaller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Flujo público (sin cuenta) de 004-solicitud-alta-taller. Las rutas nunca resuelven por `id`
 * (route-model-binding implícito expondría IDs internos) — siempre buscan por `token_publico`.
 */
class SolicitudTallerController extends Controller
{
    public function create(): View
    {
        return view('solicitudes.crear', [
            'categorias' => Categoria::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function store(CrearSolicitudTallerRequest $request, CrearSolicitudTallerAction $action): RedirectResponse
    {
        $solicitud = $action->execute($request->validated());

        return redirect()->route('solicitudes.seguimiento', $solicitud->token_publico);
    }

    public function seguimiento(string $token): View
    {
        $solicitud = SolicitudTaller::where('token_publico', $token)->with('historial')->firstOrFail();

        return view('solicitudes.seguimiento', ['solicitud' => $solicitud]);
    }

    public function cancelar(string $token, CancelarSolicitudAction $action): RedirectResponse
    {
        $solicitud = SolicitudTaller::where('token_publico', $token)->firstOrFail();

        try {
            $action->execute($solicitud);
        } catch (BusinessException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('solicitudes.seguimiento', $token)->with('status', 'Solicitud cancelada.');
    }
}
