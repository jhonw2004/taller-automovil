<?php

namespace App\Filament\Erp\Widgets;

use App\Models\Repuesto;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Ver docblock de `ClientesVehiculosStatsWidget`. Solo visible con
 * `inventario.ver` o `repuestos.ver` — mecánico solo tiene la primera, owner/shop-admin ambas.
 */
class InventarioStatsWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    private static function tienePermiso(string $slug): bool
    {
        return Filament::auth()->user()?->tienePermiso($slug, session('taller_activo_id')) ?? false;
    }

    public static function canView(): bool
    {
        return static::tienePermiso('inventario.ver') || static::tienePermiso('repuestos.ver');
    }

    protected function getStats(): array
    {
        $stockBajo = Repuesto::activos()->whereColumn('stock_actual', '<=', 'stock_minimo')->count();

        return [
            Stat::make('Repuestos activos', Repuesto::activos()->count())
                ->icon('heroicon-o-cog-6-tooth')
                ->color('info'),

            Stat::make('Con stock bajo', $stockBajo)
                ->description('Stock actual por debajo del mínimo')
                ->icon('heroicon-o-exclamation-circle')
                ->color($stockBajo > 0 ? 'danger' : 'success'),
        ];
    }
}
