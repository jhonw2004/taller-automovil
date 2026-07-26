<?php

namespace App\Filament\Admin\Resources\TallerResource\Pages;

use App\Filament\Admin\Resources\TallerResource;
use Filament\Resources\Pages\ListRecords;

class ListTalleres extends ListRecords
{
    protected static string $resource = TallerResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
