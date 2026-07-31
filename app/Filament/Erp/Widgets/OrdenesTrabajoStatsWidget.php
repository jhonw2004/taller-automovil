<?php

namespace App\Filament\Erp\Widgets;

use App\Models\OrdenTrabajo;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Ver docblock de `ClientesVehiculosStatsWidget`. Solo visible con
 * `ordenes.ver` — mecánico, recepcionista, supervisor, shop-admin y owner lo tienen; cajero y
 * vendedor no.
 */
class OrdenesTrabajoStatsWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return Filament::auth()->user()?->tienePermiso('ordenes.ver', session('taller_activo_id')) ?? false;
    }

    protected function getStats(): array
    {
        $pendientes = OrdenTrabajo::whereIn('estado', ['PENDIENTE', 'EN_DIAGNOSTICO', 'ESPERANDO_APROBACION'])->count();
        $enProgreso = OrdenTrabajo::whereIn('estado', ['EN_PROGRESO', 'PAUSADA'])->count();
        $completadasEsteMes = OrdenTrabajo::whereIn('estado', ['COMPLETADA', 'ENTREGADA'])
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        return [
            Stat::make('Órdenes pendientes', $pendientes)
                ->description('Por diagnosticar o esperando aprobación')
                ->icon('heroicon-o-clipboard-document-list')
                ->color($pendientes > 0 ? 'warning' : 'success'),

            Stat::make('Órdenes en progreso', $enProgreso)
                ->icon('heroicon-o-wrench')
                ->color('info'),

            Stat::make('Completadas este mes', $completadasEsteMes)
                ->icon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}
