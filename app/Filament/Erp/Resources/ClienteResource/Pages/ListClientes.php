<?php

namespace App\Filament\Erp\Resources\ClienteResource\Pages;

use App\Filament\Erp\Resources\ClienteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClientes extends ListRecords
{
    protected static string $resource = ClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
