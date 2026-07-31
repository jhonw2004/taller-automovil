<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Categoria;
use App\Models\Resena;
use App\Models\SolicitudTaller;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Métricas globales de la plataforma, exclusivas del Super Admin.
 * A diferencia de los widgets de `/erp` (acotados al taller activo por `BelongsToTaller`), acá
 * se consulta sin ningún scope de tenant — el Super Admin ve la plataforma completa.
 */
class PlataformaStatsWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return Filament::auth()->user()?->esSuperAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $solicitudesPendientes = SolicitudTaller::whereIn('estado', ['PENDIENTE', 'EN_REVISION'])->count();

        return [
            Stat::make('Talleres activos', Taller::where('estado', 'ACTIVO')->count())
                ->description('Visibles o no en el marketplace')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('success'),

            Stat::make('Solicitudes por revisar', $solicitudesPendientes)
                ->description('Pendientes o en revisión')
                ->descriptionIcon('heroicon-o-clock')
                ->icon('heroicon-o-inbox-stack')
                ->color($solicitudesPendientes > 0 ? 'warning' : 'success'),

            Stat::make('Talleres suspendidos', Taller::whereIn('estado', ['INACTIVO', 'SUSPENDIDO'])->count())
                ->icon('heroicon-o-no-symbol')
                ->color('danger'),

            Stat::make('Usuarios del sistema activos', UsuarioSistema::where('activo', true)->count())
                ->description('Super Admins, dueños y personal de taller')
                ->icon('heroicon-o-users')
                ->color('info'),

            Stat::make('Calificación promedio', number_format((float) Taller::where('estado', 'ACTIVO')->avg('calificacion_promedio'), 1))
                ->description(Resena::where('estado', 'PUBLICADA')->count().' reseñas publicadas')
                ->icon('heroicon-o-star')
                ->color('warning'),

            Stat::make('Categorías activas', Categoria::where('activo', true)->count())
                ->icon('heroicon-o-tag')
                ->color('info'),
        ];
    }
}
