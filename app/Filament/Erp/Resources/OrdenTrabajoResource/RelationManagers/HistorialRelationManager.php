<?php

namespace App\Filament\Erp\Resources\OrdenTrabajoResource\RelationManagers;

use App\Filament\Erp\Resources\OrdenTrabajoResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Historial de cambios de estado (append-only, constitution.md §2). Solo lectura: se crea
 * únicamente desde `CrearOrdenTrabajoAction`/`CambiarEstadoOrdenAction`/`AnularOrdenTrabajoAction`
 * — sin `headerActions`/`recordActions`, mismo patrón de solo-lectura que
 * `MovimientoInventarioResource` (010).
 */
class HistorialRelationManager extends RelationManager
{
    protected static string $relationship = 'historial';

    protected static ?string $title = 'Historial';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('estado_nuevo')
            ->columns([
                TextColumn::make('estado_anterior')
                    ->label('Estado anterior')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => $state ? (OrdenTrabajoResource::ESTADOS[$state] ?? $state) : null),
                TextColumn::make('estado_nuevo')
                    ->label('Estado nuevo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => OrdenTrabajoResource::ESTADOS[$state] ?? $state)
                    ->color(fn (string $state) => OrdenTrabajoResource::colorEstado($state)),
                TextColumn::make('usuarioSistema.username')->label('Usuario')->placeholder('—'),
                TextColumn::make('observacion')->limit(60)->placeholder('—'),
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->defaultSort('created_at', 'desc');
    }
}
