<?php

namespace App\Filament\Erp\Resources\OrdenTrabajoResource\Pages;

use App\Actions\Ordenes\AnularOrdenTrabajoAction;
use App\Actions\Ordenes\CambiarEstadoOrdenAction;
use App\Actions\Ordenes\TransicionesEstadoOrden;
use App\Exceptions\BusinessException;
use App\Filament\Erp\Resources\OrdenTrabajoResource;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/**
 * El cambio de estado y la anulación no son campos del formulario: son acciones dedicadas para
 * que el dropdown de estado solo ofrezca transiciones válidas desde el estado actual
 * (`TransicionesEstadoOrden`, 011-plan.md) y la anulación exija motivo obligatorio + permiso
 * `ordenes.anular` (011-spec.md).
 */
class EditOrdenTrabajo extends EditRecord
{
    protected static string $resource = OrdenTrabajoResource::class;

    protected function tienePermiso(string $slug): bool
    {
        return Filament::auth()->user()?->tienePermiso($slug, session('taller_activo_id')) ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cambiarEstado')
                ->label('Cambiar estado')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn () => $this->tienePermiso('ordenes.editar')
                    && collect(TransicionesEstadoOrden::MAPA[$this->getRecord()->estado] ?? [])->reject(fn ($estado) => $estado === 'ANULADA')->isNotEmpty())
                ->schema([
                    Select::make('estado')
                        ->label('Nuevo estado')
                        ->options(fn () => collect(TransicionesEstadoOrden::MAPA[$this->getRecord()->estado] ?? [])
                            ->reject(fn ($estado) => $estado === 'ANULADA')
                            ->mapWithKeys(fn ($estado) => [$estado => OrdenTrabajoResource::ESTADOS[$estado] ?? $estado]))
                        ->required(),
                    Textarea::make('observacion')->maxLength(500),
                ])
                ->requiresConfirmation()
                ->action(function (array $data) {
                    try {
                        app(CambiarEstadoOrdenAction::class)->execute(
                            $this->getRecord(),
                            $data['estado'],
                            Filament::auth()->id(),
                            $data['observacion'] ?? null,
                        );
                    } catch (BusinessException $exception) {
                        Notification::make()->danger()->title($exception->getMessage())->send();

                        return;
                    }

                    Notification::make()->success()->title('Estado actualizado.')->send();
                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->getRecord()]));
                }),
            Action::make('anular')
                ->label('Anular orden')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn () => $this->tienePermiso('ordenes.anular')
                    && TransicionesEstadoOrden::permite($this->getRecord()->estado, 'ANULADA'))
                ->schema([
                    Textarea::make('motivo')->label('Motivo de anulación')->required()->maxLength(500),
                ])
                ->requiresConfirmation()
                ->action(function (array $data) {
                    try {
                        app(AnularOrdenTrabajoAction::class)->execute(
                            $this->getRecord(),
                            $data['motivo'],
                            Filament::auth()->id(),
                        );
                    } catch (BusinessException $exception) {
                        Notification::make()->danger()->title($exception->getMessage())->send();

                        return;
                    }

                    Notification::make()->success()->title('Orden anulada.')->send();
                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->getRecord()]));
                }),
        ];
    }
}
