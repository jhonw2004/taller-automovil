<?php

namespace App\Filament\Admin\Resources\PermisoResource\Pages;

use App\Filament\Admin\Resources\PermisoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPermisos extends ListRecords
{
    protected static string $resource = PermisoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
