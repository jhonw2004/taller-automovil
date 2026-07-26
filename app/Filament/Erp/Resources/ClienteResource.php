<?php

namespace App\Filament\Erp\Resources;

use App\Filament\Erp\Resources\ClienteResource\Pages\CreateCliente;
use App\Filament\Erp\Resources\ClienteResource\Pages\EditCliente;
use App\Filament\Erp\Resources\ClienteResource\Pages\ListClientes;
use App\Models\Cliente;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
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
 * Clientes del taller activo (007-clientes-vehiculos/spec.md). No requiere `getEloquentQuery()`
 * manual como `TallerResource`/`RolResource`: `Cliente` usa el trait `BelongsToTaller`, cuyo
 * global scope ya filtra por `session('taller_activo_id')` para cualquier query, Filament
 * incluida, y auto-rellena `taller_id` al crear.
 */
class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $slug = 'clientes';

    protected static function tienePermiso(string $slug): bool
    {
        return Filament::auth()->user()?->tienePermiso($slug, session('taller_activo_id')) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::tienePermiso('clientes.ver');
    }

    public static function canCreate(): bool
    {
        return static::tienePermiso('clientes.crear');
    }

    /** @param Cliente $record */
    public static function canEdit(Model $record): bool
    {
        return static::tienePermiso('clientes.editar');
    }

    /** @param Cliente $record */
    public static function canDelete(Model $record): bool
    {
        return static::tienePermiso('clientes.eliminar');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('tipo_persona')
                ->label('Tipo de persona')
                ->options(['NATURAL' => 'Natural', 'JURIDICA' => 'Jurídica'])
                ->required()
                ->live()
                ->default('NATURAL'),
            TextInput::make('codigo')->required()->maxLength(50)->scopedUnique(),
            TextInput::make('nombre')
                ->label(fn (callable $get) => $get('tipo_persona') === 'JURIDICA' ? 'Nombre de contacto' : 'Nombre')
                ->required()
                ->maxLength(255),
            TextInput::make('apellido')
                ->maxLength(255)
                ->visible(fn (callable $get) => $get('tipo_persona') !== 'JURIDICA'),
            TextInput::make('razon_social')
                ->label('Razón social')
                ->maxLength(255)
                ->required(fn (callable $get) => $get('tipo_persona') === 'JURIDICA')
                ->visible(fn (callable $get) => $get('tipo_persona') === 'JURIDICA'),
            TextInput::make('nit_ci')->label('NIT/CI')->maxLength(50)->scopedUnique(),
            TextInput::make('telefono')->tel()->maxLength(30),
            TextInput::make('email')->email()->maxLength(255),
            TextInput::make('direccion')->maxLength(255)->columnSpanFull(),
            Textarea::make('observaciones')->maxLength(2000)->columnSpanFull(),
            Toggle::make('activo')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('vehiculos'))
            ->columns([
                TextColumn::make('codigo')->searchable()->sortable(),
                TextColumn::make('nombre_mostrar')
                    ->label('Nombre / Razón social')
                    ->state(fn (Cliente $record) => $record->tipo_persona === 'JURIDICA'
                        ? $record->razon_social
                        : trim("{$record->nombre} {$record->apellido}"))
                    ->searchable(['nombre', 'apellido', 'razon_social']),
                TextColumn::make('nit_ci')->label('NIT/CI')->searchable(),
                TextColumn::make('telefono'),
                TextColumn::make('email'),
                TextColumn::make('vehiculos_count')->label('Vehículos')->counts('vehiculos'),
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
            'index' => ListClientes::route('/'),
            'create' => CreateCliente::route('/create'),
            'edit' => EditCliente::route('/{record}/edit'),
        ];
    }
}
