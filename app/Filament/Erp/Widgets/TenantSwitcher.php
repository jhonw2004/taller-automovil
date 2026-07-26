<?php

namespace App\Filament\Erp\Widgets;

use App\Models\AsignacionRol;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Selector de taller activo (constitution.md §5): solo aparece si el usuario tiene acceso
 * vigente a más de un taller. Fijo (no se renderiza) si solo tiene uno.
 */
class TenantSwitcher extends Widget
{
    protected string $view = 'filament.erp.widgets.tenant-switcher';

    protected static bool $isLazy = false;

    public ?int $tallerSeleccionado = null;

    public function mount(): void
    {
        $this->tallerSeleccionado = session('taller_activo_id');
    }

    public static function canView(): bool
    {
        return static::talleresVigentes()->count() > 1;
    }

    /**
     * @return Collection<int, Taller>
     */
    protected static function talleresVigentes(): Collection
    {
        /** @var UsuarioSistema|null $user */
        $user = Auth::guard('sistema')->user();

        if (! $user) {
            return collect();
        }

        return $user->asignacionesVigentes()
            ->filter(fn (AsignacionRol $asignacion) => $asignacion->taller_id !== null)
            ->pluck('taller')
            ->unique('id')
            ->values();
    }

    protected function getViewData(): array
    {
        return [
            'talleres' => static::talleresVigentes(),
        ];
    }

    public function updatedTallerSeleccionado(int $value): void
    {
        if (! static::talleresVigentes()->contains('id', $value)) {
            return;
        }

        session(['taller_activo_id' => $value]);

        $this->redirect(request()->header('referer') ?? Filament::getPanel('erp')->getUrl());
    }
}
