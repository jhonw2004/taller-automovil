<?php

namespace App\Filament\Erp\Resources;

use App\Filament\Erp\Resources\OrdenTrabajoResource\Pages\CreateOrdenTrabajo;
use App\Filament\Erp\Resources\OrdenTrabajoResource\Pages\EditOrdenTrabajo;
use App\Filament\Erp\Resources\OrdenTrabajoResource\Pages\ListOrdenesTrabajo;
use App\Filament\Erp\Resources\OrdenTrabajoResource\RelationManagers\HistorialRelationManager;
use App\Filament\Erp\Resources\OrdenTrabajoResource\RelationManagers\NotasRelationManager;
use App\Filament\Erp\Resources\OrdenTrabajoResource\RelationManagers\RepuestosRelationManager;
use App\Filament\Erp\Resources\OrdenTrabajoResource\RelationManagers\ServiciosRelationManager;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\OrdenTrabajo;
use App\Models\Vehiculo;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Órdenes de trabajo del taller activo (011-ordenes-trabajo/spec.md). No requiere
 * `getEloquentQuery()` manual: `OrdenTrabajo` usa `BelongsToTaller`. La creación real pasa por
 * `CrearOrdenTrabajoAction` (código secuencial + historial inicial, ver `CreateOrdenTrabajo`), no
 * `OrdenTrabajo::create()` directo. Edición de campos simples (empleado/prioridad/fechas/
 * observaciones) sí usa el flujo estándar de Filament — `canEdit()` ya bloquea una orden ANULADA.
 * El cambio de estado y la anulación son acciones dedicadas en `EditOrdenTrabajo` (no un campo
 * `estado` editable en el formulario), para que el dropdown solo ofrezca transiciones válidas
 * (`TransicionesEstadoOrden`) y la anulación exija motivo + dispare la reposición de stock.
 */
class OrdenTrabajoResource extends Resource
{
    protected static ?string $model = OrdenTrabajo::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Órdenes de Trabajo';

    protected static ?string $modelLabel = 'Orden de Trabajo';

    protected static ?string $slug = 'ordenes-trabajo';

    public const ESTADOS = [
        'PENDIENTE' => 'Pendiente',
        'EN_DIAGNOSTICO' => 'En diagnóstico',
        'ESPERANDO_APROBACION' => 'Esperando aprobación',
        'EN_PROGRESO' => 'En progreso',
        'PAUSADA' => 'Pausada',
        'COMPLETADA' => 'Completada',
        'ENTREGADA' => 'Entregada',
        'ANULADA' => 'Anulada',
    ];

    public const PRIORIDADES = [
        'BAJA' => 'Baja',
        'MEDIA' => 'Media',
        'ALTA' => 'Alta',
    ];

    public static function colorEstado(string $estado): string
    {
        return match ($estado) {
            'PENDIENTE', 'ESPERANDO_APROBACION', 'PAUSADA' => 'warning',
            'EN_DIAGNOSTICO', 'EN_PROGRESO' => 'info',
            'COMPLETADA', 'ENTREGADA' => 'success',
            'ANULADA' => 'danger',
            default => 'gray',
        };
    }

    public static function colorPrioridad(string $prioridad): string
    {
        return match ($prioridad) {
            'ALTA' => 'danger',
            'MEDIA' => 'warning',
            default => 'gray',
        };
    }

    protected static function tienePermiso(string $slug): bool
    {
        return Filament::auth()->user()?->tienePermiso($slug, session('taller_activo_id')) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::tienePermiso('ordenes.ver');
    }

    public static function canCreate(): bool
    {
        return static::tienePermiso('ordenes.crear');
    }

    /** @param OrdenTrabajo $record */
    public static function canEdit(Model $record): bool
    {
        return static::tienePermiso('ordenes.editar') && $record->estado !== 'ANULADA';
    }

    /** @param OrdenTrabajo $record */
    public static function canDelete(Model $record): bool
    {
        return static::tienePermiso('ordenes.eliminar');
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
            TextInput::make('codigo')->disabled()->dehydrated(false)->visibleOn('edit'),
            Select::make('cliente_id')
                ->label('Cliente')
                ->relationship('cliente', 'nombre')
                ->getOptionLabelFromRecordUsing(fn (Cliente $record) => static::nombreCliente($record))
                ->searchable(['nombre', 'apellido', 'razon_social'])
                ->required()
                ->live()
                ->disabled(fn (string $operation) => $operation === 'edit')
                ->dehydrated(fn (string $operation) => $operation === 'create'),
            Select::make('vehiculo_id')
                ->label('Vehículo')
                ->options(fn (Get $get) => Vehiculo::query()
                    ->activos()
                    ->where('cliente_id', $get('cliente_id'))
                    ->get()
                    ->mapWithKeys(fn (Vehiculo $vehiculo) => [$vehiculo->id => $vehiculo->placa_formateada]))
                ->searchable()
                ->required()
                ->disabled(fn (Get $get, string $operation) => $operation === 'edit' || blank($get('cliente_id')))
                ->dehydrated(fn (string $operation) => $operation === 'create'),
            Select::make('empleado_asignado_id')
                ->label('Empleado asignado')
                ->relationship('empleadoAsignado', 'nombre', fn ($query) => $query->activos())
                ->getOptionLabelFromRecordUsing(fn (Empleado $record) => trim("{$record->nombre} {$record->apellido}"))
                ->searchable(['nombre', 'apellido'])
                ->preload(),
            Select::make('prioridad')->options(self::PRIORIDADES)->default('MEDIA')->required(),
            DatePicker::make('fecha_estimada_entrega')->label('Fecha estimada de entrega'),
            TextInput::make('kilometraje_ingreso')->label('Kilometraje de ingreso')->numeric()->minValue(0),
            Textarea::make('sintomas')->maxLength(2000)->columnSpanFull(),
            Textarea::make('diagnostico')->maxLength(2000)->columnSpanFull(),
            Textarea::make('observaciones')->maxLength(2000)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->searchable()->sortable(),
                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->state(fn (OrdenTrabajo $record) => static::nombreCliente($record->cliente))
                    ->searchable(),
                TextColumn::make('vehiculo.placa')
                    ->label('Vehículo')
                    ->formatStateUsing(fn (OrdenTrabajo $record) => $record->vehiculo->placa_formateada)
                    ->searchable(),
                TextColumn::make('empleadoAsignado.nombre')
                    ->label('Empleado')
                    ->state(fn (OrdenTrabajo $record) => $record->empleadoAsignado
                        ? trim("{$record->empleadoAsignado->nombre} {$record->empleadoAsignado->apellido}")
                        : '—'),
                TextColumn::make('estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::ESTADOS[$state] ?? $state)
                    ->color(fn (string $state) => static::colorEstado($state)),
                TextColumn::make('prioridad')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::PRIORIDADES[$state] ?? $state)
                    ->color(fn (string $state) => static::colorPrioridad($state)),
                TextColumn::make('total')->money('BOB')->sortable(),
                TextColumn::make('fecha_recepcion')->label('Recepción')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('estado')->options(self::ESTADOS),
                SelectFilter::make('prioridad')->options(self::PRIORIDADES),
            ])
            ->defaultSort('fecha_recepcion', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ServiciosRelationManager::class,
            RepuestosRelationManager::class,
            NotasRelationManager::class,
            HistorialRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrdenesTrabajo::route('/'),
            'create' => CreateOrdenTrabajo::route('/create'),
            'edit' => EditOrdenTrabajo::route('/{record}/edit'),
        ];
    }
}
