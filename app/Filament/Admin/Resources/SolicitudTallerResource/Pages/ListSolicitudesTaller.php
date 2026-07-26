<?php

namespace App\Filament\Admin\Resources\SolicitudTallerResource\Pages;

use App\Filament\Admin\Resources\SolicitudTallerResource;
use Filament\Resources\Pages\ListRecords;

class ListSolicitudesTaller extends ListRecords
{
    protected static string $resource = SolicitudTallerResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
