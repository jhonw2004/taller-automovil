<?php

namespace App\Filament\Erp\Resources;

use App\Filament\Erp\Resources\VehiculoResource\Pages\CreateVehiculo;
use App\Filament\Erp\Resources\VehiculoResource\Pages\EditVehiculo;
use App\Filament\Erp\Resources\VehiculoResource\Pages\ListVehiculos;
use App\Models\Cliente;
use App\Models\Vehiculo;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
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
 * Vehículos del taller activo (007-clientes-vehiculos/spec.md). Igual que `ClienteResource`, sin
 * `getEloquentQuery()` manual — `Vehiculo` usa `BelongsToTaller`.
 */
class VehiculoResource extends Resource
{
    protected static ?string $model = Vehiculo::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Vehículos';

    protected static ?string $slug = 'vehiculos';

    protected static function tienePermiso(string $slug): bool
    {
        return Filament::auth()->user()?->tienePermiso($slug, session('taller_activo_id')) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::tienePermiso('vehiculos.ver');
    }

    public static function canCreate(): bool
    {
        return static::tienePermiso('vehiculos.crear');
    }

    /** @param Vehiculo $record */
    public static function canEdit(Model $record): bool
    {
        return static::tienePermiso('vehiculos.editar');
    }

    /** @param Vehiculo $record */
    public static function canDelete(Model $record): bool
    {
        return static::tienePermiso('vehiculos.eliminar');
    }

    protected static function nombreCliente(Cliente $cliente): string
    {
        return $cliente->tipo_persona === 'JURIDICA'
            ? $cliente->razon_social
            : trim("{$cliente->nombre} {$cliente->apellido}");
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('cliente_id')
                ->label('Cliente')
                ->relationship('cliente', 'nombre')
                ->getOptionLabelFromRecordUsing(fn (Cliente $record) => static::nombreCliente($record))
                ->searchable(['nombre', 'apellido', 'razon_social'])
                ->required(),
            TextInput::make('placa')
                ->required()
                ->maxLength(10)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) => $set(
                    'placa',
                    $state ? strtoupper(str_replace(['-', ' '], '', $state)) : $state,
                ))
                ->rule('regex:/^[A-Z]{3}[0-9]{3}$/')
                ->scopedUnique()
                ->helperText('Formato ABC123 (sin guion) — se normaliza automáticamente.'),
            TextInput::make('marca')->maxLength(255),
            TextInput::make('modelo')->maxLength(255),
            TextInput::make('anio')->label('Año')->numeric()->minValue(1900)->maxValue((int) date('Y') + 1),
            TextInput::make('color')->maxLength(255),
            TextInput::make('vin')->label('VIN')->maxLength(50),
            Select::make('tipo_vehiculo')
                ->label('Tipo')
                ->options([
                    'AUTO' => 'Auto',
                    'MOTO' => 'Moto',
                    'CAMIONETA' => 'Camioneta',
                    'CAMION' => 'Camión',
                    'OTRO' => 'Otro',
                ])
                ->required()
                ->default('AUTO'),
            TextInput::make('kilometraje')->numeric()->minValue(0)->default(0),
            Toggle::make('activo')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('placa')
                    ->formatStateUsing(fn (Vehiculo $record) => $record->placa_formateada)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->state(fn (Vehiculo $record) => static::nombreCliente($record->cliente))
                    ->searchable(),
                TextColumn::make('marca'),
                TextColumn::make('modelo'),
                TextColumn::make('anio')->label('Año'),
                TextColumn::make('tipo_vehiculo')->label('Tipo')->badge(),
                IconColumn::make('activo')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('activo'),
            ])
            ->defaultSort('placa');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVehiculos::route('/'),
            'create' => CreateVehiculo::route('/create'),
            'edit' => EditVehiculo::route('/{record}/edit'),
        ];
    }
}
