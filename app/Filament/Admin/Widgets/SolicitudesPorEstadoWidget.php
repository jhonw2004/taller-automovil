<?php

namespace App\Filament\Admin\Widgets;

use App\Models\SolicitudTaller;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

/**
 * Segundo gráfico exclusivo del Super Admin: distribución de
 * solicitudes de alta de taller por estado, todo el histórico (no solo las pendientes que ya
 * cubre `PlataformaStatsWidget`).
 */
class SolicitudesPorEstadoWidget extends ChartWidget
{
    protected ?string $heading = 'Solicitudes de alta por estado';

    private const ETIQUETAS = [
        'PENDIENTE' => 'Pendiente',
        'EN_REVISION' => 'En revisión',
        'APROBADA' => 'Aprobada',
        'RECHAZADA' => 'Rechazada',
        'COMPLETADA' => 'Completada',
        'CANCELADA' => 'Cancelada',
    ];

    private const COLORES = [
        'PENDIENTE' => '#a1a1aa',
        'EN_REVISION' => '#52525b',
        'APROBADA' => '#22c55e',
        'RECHAZADA' => '#ef4444',
        'COMPLETADA' => '#3f3f46',
        'CANCELADA' => '#d4d4d8',
    ];

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return Filament::auth()->user()?->esSuperAdmin() ?? false;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $conteos = SolicitudTaller::selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $estados = array_keys(self::ETIQUETAS);

        return [
            'datasets' => [
                [
                    'data' => array_map(fn (string $estado) => (int) ($conteos[$estado] ?? 0), $estados),
                    'backgroundColor' => array_values(self::COLORES),
                ],
            ],
            'labels' => array_map(fn (string $estado) => self::ETIQUETAS[$estado], $estados),
        ];
    }
}
