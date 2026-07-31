<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Categoria;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

/**
 * Variedad de tipos de widget para el Super Admin: además de las
 * tarjetas de `PlataformaStatsWidget`, un gráfico de barras con la distribución real de
 * talleres activos por categoría (relación muchos-a-muchos `talleres_categorias`).
 */
class TalleresPorCategoriaWidget extends ChartWidget
{
    protected ?string $heading = 'Talleres por categoría';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return Filament::auth()->user()?->esSuperAdmin() ?? false;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $categorias = Categoria::withCount(['talleres' => function ($query) {
            $query->where('estado', 'ACTIVO');
        }])
            ->orderByDesc('talleres_count')
            ->take(8)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Talleres activos',
                    'data' => $categorias->pluck('talleres_count')->all(),
                    'backgroundColor' => '#ff5a00',
                ],
            ],
            'labels' => $categorias->pluck('nombre')->all(),
        ];
    }
}
