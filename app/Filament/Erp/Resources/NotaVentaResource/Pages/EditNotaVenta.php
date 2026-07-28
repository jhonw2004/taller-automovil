<?php

namespace App\Filament\Erp\Resources\NotaVentaResource\Pages;

use App\Actions\Notas\AnularNotaVentaAction;
use App\Actions\Notas\TransicionesEstadoNota;
use App\Exceptions\BusinessException;
use App\Filament\Erp\Resources\NotaVentaResource;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/**
 * Nada es editable después de crear la nota (012-tasks.md no define ninguna Action de edición de
 * líneas/descuento) — el formulario de `NotaVentaResource::form()` solo muestra campos deshabilitados
 * en esta página. La única acción real es "Anular Nota", igual patrón que `EditOrdenTrabajo` (011).
 */
class EditNotaVenta extends EditRecord
{
    protected static string $resource = NotaVentaResource::class;

    protected function tienePermiso(string $slug): bool
    {
        return Filament::auth()->user()?->tienePermiso($slug, session('taller_activo_id')) ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('anular')
                ->label('Anular nota')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn () => $this->tienePermiso('notas.anular')
                    && TransicionesEstadoNota::permite($this->getRecord()->estado, 'ANULADA'))
                ->schema([
                    Textarea::make('motivo')->label('Motivo de anulación')->required()->maxLength(500),
                ])
                ->requiresConfirmation()
                ->action(function (array $data) {
                    try {
                        app(AnularNotaVentaAction::class)->execute(
                            $this->getRecord(),
                            $data['motivo'],
                            Filament::auth()->id(),
                        );
                    } catch (BusinessException $exception) {
                        Notification::make()->danger()->title($exception->getMessage())->send();

                        return;
                    }

                    Notification::make()->success()->title('Nota anulada.')->send();
                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->getRecord()]));
                }),
        ];
    }
}
