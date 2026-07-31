<?php

namespace App\Filament\Erp\Widgets;

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\NotaVenta;
use App\Models\OrdenTrabajo;
use App\Models\Repuesto;
use App\Models\Vehiculo;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dashboard de métricas por rol (`/erp`), acotado por permisos vía `tienePermiso()` — un mismo
 * dashboard renderiza tarjetas distintas según el rol (mecánico ve órdenes+inventario, cajero ve
 * ventas+clientes, owner ve todo). Antes eran 5 `StatsOverviewWidget` separados (uno por área),
 * cada uno con su propio contenedor/heading — mismo dato, un solo widget con todas las tarjetas
 * en una sola grilla se ve mucho menos recargado.
 */
class ResumenTallerWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected int|array|null $columns = [
        'md' => 2,
        'xl' => 3,
    ];

    private static function tienePermiso(string $slug): bool
    {
        return Filament::auth()->user()?->tienePermiso($slug, session('taller_activo_id')) ?? false;
    }

    public static function canView(): bool
    {
        return static::tienePermiso('ordenes.ver')
            || static::tienePermiso('notas.ver')
            || static::tienePermiso('inventario.ver')
            || static::tienePermiso('repuestos.ver')
            || static::tienePermiso('clientes.ver')
            || static::tienePermiso('vehiculos.ver')
            || static::tienePermiso('empleados.ver');
    }

    protected function getStats(): array
    {
        return [
            ...$this->statsOrdenes(),
            ...$this->statsVentas(),
            ...$this->statsInventario(),
            ...$this->statsClientesVehiculos(),
            ...$this->statsEmpleados(),
        ];
    }

    private function statsOrdenes(): array
    {
        if (! static::tienePermiso('ordenes.ver')) {
            return [];
        }

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

    private function statsVentas(): array
    {
        if (! static::tienePermiso('notas.ver')) {
            return [];
        }

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

    private function statsInventario(): array
    {
        if (! static::tienePermiso('inventario.ver') && ! static::tienePermiso('repuestos.ver')) {
            return [];
        }

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

    private function statsClientesVehiculos(): array
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

    private function statsEmpleados(): array
    {
        if (! static::tienePermiso('empleados.ver')) {
            return [];
        }

        return [
            Stat::make('Empleados activos', Empleado::activos()->count())
                ->icon('heroicon-o-identification')
                ->color('info'),
        ];
    }
}
