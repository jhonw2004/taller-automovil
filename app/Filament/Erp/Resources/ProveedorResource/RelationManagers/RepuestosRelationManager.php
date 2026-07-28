<?php

namespace App\Filament\Erp\Resources\ProveedorResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Repuestos que suministra este proveedor (010-inventario-repuestos/plan.md): pivot
 * `repuestos_proveedores` con `codigo_proveedor`/`precio_referencia`/`tiempo_entrega_dias`/
 * `es_principal`. Solo se gestiona desde `ProveedorResource` (no duplicado en `RepuestoResource`)
 * — el flujo natural es "ver la lista de precios de un proveedor", no al revés.
 */
class RepuestosRelationManager extends RelationManager
{
    protected static string $relationship = 'repuestos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('codigo_proveedor')->label('Código del proveedor')->maxLength(50),
            TextInput::make('precio_referencia')->label('Precio referencia')->numeric()->prefix('Bs')->minValue(0),
            TextInput::make('tiempo_entrega_dias')->label('Tiempo de entrega (días)')->numeric()->minValue(0),
            Toggle::make('es_principal')->label('Proveedor principal'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                TextColumn::make('codigo')->searchable()->sortable(),
                TextColumn::make('nombre')->searchable()->sortable(),
                TextColumn::make('codigo_proveedor')->label('Código proveedor'),
                TextColumn::make('precio_referencia')->label('Precio referencia')->money('BOB'),
                TextColumn::make('tiempo_entrega_dias')->label('Entrega (días)'),
                IconColumn::make('es_principal')->label('Principal')->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->where('activo', true))
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('codigo_proveedor')->label('Código del proveedor')->maxLength(50),
                        TextInput::make('precio_referencia')->label('Precio referencia')->numeric()->prefix('Bs')->minValue(0),
                        TextInput::make('tiempo_entrega_dias')->label('Tiempo de entrega (días)')->numeric()->minValue(0),
                        Toggle::make('es_principal')->label('Proveedor principal'),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
