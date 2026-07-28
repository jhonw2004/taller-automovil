<?php

namespace App\Filament\Admin\Resources\UnidadMedidaResource\Pages;

use App\Filament\Admin\Resources\UnidadMedidaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUnidadesMedida extends ListRecords
{
    protected static string $resource = UnidadMedidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
