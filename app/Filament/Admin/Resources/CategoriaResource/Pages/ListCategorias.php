<?php

namespace App\Filament\Admin\Resources\CategoriaResource\Pages;

use App\Filament\Admin\Resources\CategoriaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCategorias extends ListRecords
{
    protected static string $resource = CategoriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
