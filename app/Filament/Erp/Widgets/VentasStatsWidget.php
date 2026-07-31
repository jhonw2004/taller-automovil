<?php

namespace App\Filament\Erp\Widgets;

use App\Models\NotaVenta;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Ver docblock de `ClientesVehiculosStatsWidget`. Solo visible con
 * `notas.ver` — cajero, vendedor, supervisor, shop-admin y owner lo tienen; mecánico y
 * recepcionista no ven cifras de venta.
 */
class VentasStatsWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return Filament::auth()->user()?->tienePermiso('notas.ver', session('taller_activo_id')) ?? false;
    }

    protected function getStats(): array
    {
        $delMes = NotaVenta::where('estado', '!=', 'ANULADA')
            ->whereMonth('fecha_emision', now()->month)
            ->whereYear('fecha_emision', now()->year);

        $totalMes = (clone $delMes)->sum('total');
        $cobradoMes = (clone $delMes)->sum('monto_pagado');
        $pendientesPago = NotaVenta::where('estado', 'PENDIENTE')->count();

        return [
            Stat::make('Ventas del mes', 'Bs. '.number_format((float) $totalMes, 2))
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Cobrado este mes', 'Bs. '.number_format((float) $cobradoMes, 2))
                ->icon('heroicon-o-credit-card')
                ->color('info'),

            Stat::make('Notas pendientes de pago', $pendientesPago)
                ->icon('heroicon-o-exclamation-triangle')
                ->color($pendientesPago > 0 ? 'warning' : 'success'),
        ];
    }
}
