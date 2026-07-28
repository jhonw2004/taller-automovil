<?php

namespace App\Filament\Erp\Resources\ProveedorResource\Pages;

use App\Filament\Erp\Resources\ProveedorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProveedores extends ListRecords
{
    protected static string $resource = ProveedorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
