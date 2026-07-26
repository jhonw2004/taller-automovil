<?php

namespace App\Filament\Erp\Resources\AsignacionRolResource\Pages;

use App\Filament\Erp\Resources\AsignacionRolResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAsignacionesRol extends ListRecords
{
    protected static string $resource = AsignacionRolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
