<?php

namespace App\Filament\Erp\Resources\RolResource\Pages;

use App\Filament\Erp\Resources\RolResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRols extends ListRecords
{
    protected static string $resource = RolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
