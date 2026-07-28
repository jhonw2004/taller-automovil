<?php

namespace App\Filament\Erp\Resources\NotaVentaResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Líneas de la nota (012-plan.md/spec.md). Solo lectura: no hay una máquina de estados por línea
 * ni una Action para editarlas después de creada la nota — se fijan todas juntas en
 * `CrearNotaVentaDesdeOrdenAction`/`CrearNotaVentaDirectaAction`, mismo criterio de solo-lectura
 * que `HistorialRelationManager` (011).
 */
class LineasRelationManager extends RelationManager
{
    protected static string $relationship = 'lineas';

    protected static ?string $title = 'Líneas';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descripcion')
            ->columns([
                TextColumn::make('descripcion'),
                TextColumn::make('cantidad')->numeric(3),
                TextColumn::make('precio_unitario')->label('Precio unitario')->money('BOB'),
                TextColumn::make('descuento')->money('BOB'),
                TextColumn::make('subtotal')->money('BOB'),
            ])
            ->headerActions([])
            ->recordActions([]);
    }
}
