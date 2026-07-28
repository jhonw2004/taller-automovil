<?php

namespace App\Filament\Erp\Resources\NotaVentaResource\Pages;

use App\Filament\Erp\Resources\NotaVentaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotasVenta extends ListRecords
{
    protected static string $resource = NotaVentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
