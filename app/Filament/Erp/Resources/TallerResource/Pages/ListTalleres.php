<?php

namespace App\Filament\Erp\Resources\TallerResource\Pages;

use App\Filament\Erp\Resources\TallerResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListTalleres extends ListRecords
{
    protected static string $resource = TallerResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return parent::table($table)->recordActions([
            EditAction::make(),
        ]);
    }
}
