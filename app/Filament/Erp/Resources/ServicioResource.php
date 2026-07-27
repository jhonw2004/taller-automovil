<?php

namespace App\Filament\Erp\Resources;

use App\Filament\Erp\Resources\ServicioResource\Pages\CreateServicio;
use App\Filament\Erp\Resources\ServicioResource\Pages\EditServicio;
use App\Filament\Erp\Resources\ServicioResource\Pages\ListServicios;
use App\Models\ServicioCatalogo;
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
 * Catálogo de servicios del taller activo (009-catalogo-servicios/spec.md). No requiere
 * `getEloquentQuery()` manual: `ServicioCatalogo` usa `BelongsToTaller`, cuyo global scope ya
 * filtra por `session('taller_activo_id')` para cualquier query, Filament incluida.
 */
class ServicioResource extends Resource
{
    protected static ?string $model = ServicioCatalogo::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Servicios';

    protected static ?string $modelLabel = 'Servicio';

    protected static ?string $slug = 'servicios';

    protected static function tienePermiso(string $slug): bool
    {
        return Filament::auth()->user()?->tienePermiso($slug, session('taller_activo_id')) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::tienePermiso('servicios.ver');
    }

    public static function canCreate(): bool
    {
        return static::tienePermiso('servicios.crear');
    }

    /** @param ServicioCatalogo $record */
    public static function canEdit(Model $record): bool
    {
        return static::tienePermiso('servicios.editar');
    }

    /** @param ServicioCatalogo $record */
    public static function canDelete(Model $record): bool
    {
        return static::tienePermiso('servicios.eliminar');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('codigo')->required()->maxLength(50)->scopedUnique(),
            TextInput::make('nombre')->required()->maxLength(255),
            Textarea::make('descripcion')->maxLength(2000)->columnSpanFull(),
            TextInput::make('precio_base')
                ->label('Precio base')
                ->numeric()
                ->prefix('Bs')
                ->minValue(0)
                ->required()
                ->default(0),
            TextInput::make('duracion_minutos')
                ->label('Duración (minutos)')
                ->numeric()
                ->minValue(1),
            Toggle::make('activo')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->searchable()->sortable(),
                TextColumn::make('nombre')->searchable()->sortable(),
                TextColumn::make('precio_base')->label('Precio base')->money('BOB')->sortable(),
                TextColumn::make('duracion_minutos')->label('Duración (min)'),
                IconColumn::make('activo')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('activo'),
            ])
            ->defaultSort('nombre');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServicios::route('/'),
            'create' => CreateServicio::route('/create'),
            'edit' => EditServicio::route('/{record}/edit'),
        ];
    }
}
