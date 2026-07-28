<?php

namespace App\Livewire;

use App\Actions\Notificaciones\MarcarNotificacionLeidaAction;
use App\Models\Notificacion;
use App\Models\UsuarioSistema;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Campana de notificaciones del topbar ERP/Admin (014-plan.md), registrada como
 * `notificaciones-bell` (`AppServiceProvider::boot()`) y montada vía `renderHook(TOPBAR_END)` en
 * ambos `PanelProvider` — primer componente Livewire "puro" del proyecto (hasta ahora todo
 * Livewire llegaba envuelto por Filament: Resources, Pages, Widgets).
 */
class NotificacionesBell extends Component
{
    public function getNotificacionesProperty()
    {
        $usuario = $this->usuario();

        if (! $usuario) {
            return collect();
        }

        return Notificacion::query()
            ->where('usuario_sistema_id', $usuario->id)
            ->noLeidas()
            ->latest()
            ->limit(10)
            ->get();
    }

    public function getCantidadNoLeidasProperty(): int
    {
        $usuario = $this->usuario();

        if (! $usuario) {
            return 0;
        }

        return Notificacion::query()
            ->where('usuario_sistema_id', $usuario->id)
            ->noLeidas()
            ->count();
    }

    public function marcarLeida(int $notificacionId): void
    {
        $usuario = $this->usuario();

        if (! $usuario) {
            return;
        }

        $notificacion = Notificacion::query()->findOrFail($notificacionId);

        app(MarcarNotificacionLeidaAction::class)->execute($notificacion, $usuario);
    }

    private function usuario(): ?UsuarioSistema
    {
        /** @var UsuarioSistema|null */
        return Auth::guard('sistema')->user();
    }

    public function render()
    {
        return view('livewire.notificaciones-bell');
    }
}
