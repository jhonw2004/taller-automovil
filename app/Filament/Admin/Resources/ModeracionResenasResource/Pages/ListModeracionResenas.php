<?php

namespace App\Filament\Admin\Resources\ModeracionResenasResource\Pages;

use App\Filament\Admin\Resources\ModeracionResenasResource;
use Filament\Resources\Pages\ListRecords;

class ListModeracionResenas extends ListRecords
{
    protected static string $resource = ModeracionResenasResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
