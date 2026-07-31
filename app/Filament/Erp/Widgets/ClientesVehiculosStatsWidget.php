<?php

namespace App\Filament\Erp\Widgets;

use App\Models\Cliente;
use App\Models\Vehiculo;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Métricas del taller activo (dashboard de métricas por rol), acotadas por rol vía permisos — no todos
 * los roles ven las mismas tarjetas. `Cliente`/`Vehiculo` usan `BelongsToTaller`: el global scope
 * ya filtra por `session('taller_activo_id')`, no hace falta repetirlo acá.
 */
class ClientesVehiculosStatsWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    private static function tienePermiso(string $slug): bool
    {
        return Filament::auth()->user()?->tienePermiso($slug, session('taller_activo_id')) ?? false;
    }

    public static function canView(): bool
    {
        return static::tienePermiso('clientes.ver') || static::tienePermiso('vehiculos.ver');
    }

    protected function getStats(): array
    {
        $stats = [];

        if (static::tienePermiso('clientes.ver')) {
            $stats[] = Stat::make('Clientes activos', Cliente::activos()->count())
                ->icon('heroicon-o-user-group')
                ->color('info');
        }

        if (static::tienePermiso('vehiculos.ver')) {
            $stats[] = Stat::make('Vehículos registrados', Vehiculo::activos()->count())
                ->icon('heroicon-o-truck')
                ->color('info');
        }

        return $stats;
    }
}
