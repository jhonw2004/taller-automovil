<?php

namespace App\Filament\Erp\Resources\RepuestoResource\Pages;

use App\Filament\Erp\Resources\RepuestoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRepuestos extends ListRecords
{
    protected static string $resource = RepuestoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
