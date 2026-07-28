<?php

namespace App\Filament\Erp\Resources\OrdenTrabajoResource\Pages;

use App\Filament\Erp\Resources\OrdenTrabajoResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * "Lista por estado" de 011-plan.md: una pestaña por estado (más "Todas"), no un Kanban con
 * drag-and-drop — no hay ningún paquete de Kanban instalado en el proyecto (`composer.json`
 * verificado) y construir uno a mano no es requisito de ningún criterio de aceptación de
 * `011-spec.md` (que solo exige que las transiciones se validen, no una interacción de arrastrar).
 */
class ListOrdenesTrabajo extends ListRecords
{
    protected static string $resource = OrdenTrabajoResource::class;

    public function getTabs(): array
    {
        $tabs = ['todas' => Tab::make('Todas')];

        foreach (OrdenTrabajoResource::ESTADOS as $estado => $label) {
            $tabs[$estado] = Tab::make($label)
                ->modifyQueryUsing(fn ($query) => $query->where('estado', $estado));
        }

        return $tabs;
    }
}
