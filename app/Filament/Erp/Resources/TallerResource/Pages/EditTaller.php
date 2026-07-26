<?php

namespace App\Filament\Erp\Resources\TallerResource\Pages;

use App\Actions\Talleres\CambiarVisibilidadTallerAction;
use App\Exceptions\BusinessException;
use App\Filament\Erp\Resources\TallerResource;
use App\Models\Taller;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTaller extends EditRecord
{
    protected static string $resource = TallerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cambiarVisibilidad')
                ->label(fn (Taller $record) => $record->visible_en_mapa ? 'Ocultar del mapa' : 'Mostrar en el mapa')
                ->color(fn (Taller $record) => $record->visible_en_mapa ? 'gray' : 'success')
                ->requiresConfirmation()
                ->action(function (Taller $record) {
                    try {
                        app(CambiarVisibilidadTallerAction::class)->execute(
                            $record,
                            ! $record->visible_en_mapa,
                            Filament::auth()->user(),
                        );
                    } catch (BusinessException $exception) {
                        Notification::make()->danger()->title($exception->getMessage())->send();

                        return;
                    }

                    Notification::make()->success()->title('Visibilidad actualizada.')->send();
                }),
        ];
    }
}
