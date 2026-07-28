<?php

namespace App\Filament\Erp\Resources;

use App\Filament\Erp\Resources\ProveedorResource\Pages\CreateProveedor;
use App\Filament\Erp\Resources\ProveedorResource\Pages\EditProveedor;
use App\Filament\Erp\Resources\ProveedorResource\Pages\ListProveedores;
use App\Filament\Erp\Resources\ProveedorResource\RelationManagers\RepuestosRelationManager;
use App\Models\Proveedor;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Proveedores del taller activo (010-inventario-repuestos/spec.md). CRUD simple, mismo patrón
 * que `ClienteResource` — `Proveedor` usa `BelongsToTaller`.
 */
class ProveedorResource extends Resource
{
    protected static ?string $model = Proveedor::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Proveedores';

    protected static ?string $modelLabel = 'Proveedor';

    protected static ?string $slug = 'proveedores';

    protected static function tienePermiso(string $slug): bool
    {
        return Filament::auth()->user()?->tienePermiso($slug, session('taller_activo_id')) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::tienePermiso('proveedores.ver');
    }

    public static function canCreate(): bool
    {
        return static::tienePermiso('proveedores.crear');
    }

    /** @param Proveedor $record */
    public static function canEdit(Model $record): bool
    {
        return static::tienePermiso('proveedores.editar');
    }

    /** @param Proveedor $record */
    public static function canDelete(Model $record): bool
    {
        return static::tienePermiso('proveedores.eliminar');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->required()->maxLength(255),
            TextInput::make('contacto')->maxLength(255),
            TextInput::make('telefono')->tel()->maxLength(30),
            TextInput::make('email')->email()->maxLength(255),
            TextInput::make('direccion')->maxLength(255),
            TextInput::make('nit')->label('NIT')->maxLength(50)->scopedUnique(),
            Textarea::make('observaciones')->maxLength(2000)->columnSpanFull(),
            Toggle::make('activo')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->searchable()->sortable(),
                TextColumn::make('contacto')->searchable(),
                TextColumn::make('telefono'),
                TextColumn::make('email')->searchable(),
                IconColumn::make('activo')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('activo'),
            ])
            ->defaultSort('nombre');
    }

    public static function getRelations(): array
    {
        return [
            RepuestosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProveedores::route('/'),
            'create' => CreateProveedor::route('/create'),
            'edit' => EditProveedor::route('/{record}/edit'),
        ];
    }
}
