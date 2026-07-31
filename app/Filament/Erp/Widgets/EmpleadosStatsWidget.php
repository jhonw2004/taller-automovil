<?php

namespace App\Filament\Erp\Widgets;

use App\Models\Empleado;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Ver docblock de `ClientesVehiculosStatsWidget`. Solo visible con
 * `empleados.ver` — owner, shop-admin y supervisor; cajero, vendedor, mecánico y recepcionista
 * no administran personal.
 */
class EmpleadosStatsWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return Filament::auth()->user()?->tienePermiso('empleados.ver', session('taller_activo_id')) ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Empleados activos', Empleado::activos()->count())
                ->icon('heroicon-o-identification')
                ->color('info'),
        ];
    }
}
