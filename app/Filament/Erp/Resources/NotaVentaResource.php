<?php

namespace App\Filament\Erp\Resources;

use App\Filament\Erp\Resources\NotaVentaResource\Pages\CreateNotaVenta;
use App\Filament\Erp\Resources\NotaVentaResource\Pages\EditNotaVenta;
use App\Filament\Erp\Resources\NotaVentaResource\Pages\ListNotasVenta;
use App\Filament\Erp\Resources\NotaVentaResource\RelationManagers\LineasRelationManager;
use App\Models\Cliente;
use App\Models\NotaVenta;
use App\Models\OrdenTrabajo;
use App\Models\Repuesto;
use App\Models\ServicioCatalogo;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Notas de venta del taller activo (012-notas-venta/spec.md). No requiere `getEloquentQuery()`
 * manual: `NotaVenta` usa `BelongsToTaller`. La creación real pasa por
 * `CrearNotaVentaDesdeOrdenAction`/`CrearNotaVentaDirectaAction` (nunca `NotaVenta::create()`
 * directo, ver `CreateNotaVenta`) — el formulario alterna entre ambos orígenes con el campo
 * `origen`, que no es una columna del modelo. `estado` no es editable: solo cambia vía
 * `AnularNotaVentaAction` (acción de cabecera en `EditNotaVenta`) o, en `013-pagos`, al registrar
 * pagos (no implementado todavía).
 */
class NotaVentaResource extends Resource
{
    protected static ?string $model = NotaVenta::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Notas de Venta';

    protected static ?string $modelLabel = 'Nota de Venta';

    protected static ?string $slug = 'notas-venta';

    public const ESTADOS = [
        'EMITIDA' => 'Emitida',
        'PENDIENTE' => 'Pendiente',
        'PAGADA' => 'Pagada',
        'ANULADA' => 'Anulada',
    ];

    public static function colorEstado(string $estado): string
    {
        return match ($estado) {
            'EMITIDA' => 'info',
            'PENDIENTE' => 'warning',
            'PAGADA' => 'success',
            'ANULADA' => 'danger',
            default => 'gray',
        };
    }

    protected static function tienePermiso(string $slug): bool
    {
        return Filament::auth()->user()?->tienePermiso($slug, session('taller_activo_id')) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::tienePermiso('notas.ver');
    }

    public static function canCreate(): bool
    {
        return static::tienePermiso('notas.crear');
    }

    /** @param NotaVenta $record */
    public static function canEdit(Model $record): bool
    {
        return static::tienePermiso('notas.editar');
    }

    /** @param NotaVenta $record */
    public static function canDelete(Model $record): bool
    {
        return static::tienePermiso('notas.eliminar');
    }

    public static function nombreCliente(?Cliente $cliente): string
    {
        if (! $cliente) {
            return 'Venta directa';
        }

        return $cliente->tipo_persona === 'JURIDICA'
            ? $cliente->razon_social
            : trim("{$cliente->nombre} {$cliente->apellido}");
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('codigo')->disabled()->dehydrated(false)->visibleOn('edit'),
            TextInput::make('estado')
                ->disabled()
                ->dehydrated(false)
                ->formatStateUsing(fn (?string $state) => self::ESTADOS[$state] ?? $state)
                ->visibleOn('edit'),
            TextInput::make('subtotal')->disabled()->dehydrated(false)->prefix('Bs')->visibleOn('edit'),
            TextInput::make('descuento')->disabled()->dehydrated(false)->prefix('Bs')->visibleOn('edit'),
            TextInput::make('total')->disabled()->dehydrated(false)->prefix('Bs')->visibleOn('edit'),
            TextInput::make('monto_pagado')->label('Pagado')->disabled()->dehydrated(false)->prefix('Bs')->visibleOn('edit'),
            TextInput::make('saldo')->disabled()->dehydrated(false)->prefix('Bs')->visibleOn('edit'),
            Radio::make('origen')
                ->label('Origen')
                ->options([
                    'directa' => 'Venta directa',
                    'orden' => 'Desde una orden de trabajo',
                ])
                ->default('directa')
                ->live()
                ->required()
                ->visibleOn('create'),
            Select::make('orden_trabajo_id')
                ->label('Orden de trabajo')
                ->options(fn () => OrdenTrabajo::query()
                    ->whereIn('estado', ['COMPLETADA', 'ENTREGADA'])
                    ->get()
                    ->mapWithKeys(fn (OrdenTrabajo $orden) => [$orden->id => $orden->codigo]))
                ->searchable()
                ->required(fn (Get $get) => $get('origen') === 'orden')
                ->visible(fn (Get $get) => $get('origen') === 'orden')
                ->visibleOn('create'),
            Select::make('cliente_id')
                ->label('Cliente')
                ->relationship('cliente', 'nombre')
                ->getOptionLabelFromRecordUsing(fn (Cliente $record) => static::nombreCliente($record))
                ->searchable(['nombre', 'apellido', 'razon_social'])
                ->visible(fn (Get $get) => $get('origen') !== 'orden')
                ->visibleOn('create'),
            // Sin ->minItems(1): en Filament v5 esa validación siembra un item por defecto en el
            // estado del Repeater aunque esté oculto (origen 'orden'), lo que dispara "repuesto_id
            // requerido" incluso cuando no aplica. "Al menos una línea" (012-spec.md) ya lo exige
            // `CrearNotaVentaDirectaAction` con un mensaje de negocio legible.
            Repeater::make('lineas')
                ->label('Líneas')
                ->default([])
                ->schema([
                    Select::make('tipo')
                        ->label('Tipo')
                        ->options([
                            'repuesto' => 'Repuesto',
                            'servicio' => 'Servicio',
                            'personalizada' => 'Personalizada',
                        ])
                        ->default('repuesto')
                        ->live()
                        ->required(),
                    Select::make('repuesto_id')
                        ->label('Repuesto')
                        ->options(fn () => Repuesto::query()->activos()->pluck('nombre', 'id'))
                        ->searchable()
                        ->visible(fn (Get $get) => $get('tipo') === 'repuesto')
                        ->required(fn (Get $get) => $get('../../origen') === 'directa' && $get('tipo') === 'repuesto'),
                    Select::make('servicio_catalogo_id')
                        ->label('Servicio')
                        ->options(fn () => ServicioCatalogo::query()->activos()->pluck('nombre', 'id'))
                        ->searchable()
                        ->visible(fn (Get $get) => $get('tipo') === 'servicio')
                        ->required(fn (Get $get) => $get('../../origen') === 'directa' && $get('tipo') === 'servicio'),
                    TextInput::make('descripcion')
                        ->label('Descripción')
                        ->helperText('Opcional para repuesto/servicio: si se deja vacío se usa el nombre del catálogo.')
                        ->visible(fn (Get $get) => $get('tipo') === 'personalizada')
                        ->required(fn (Get $get) => $get('../../origen') === 'directa' && $get('tipo') === 'personalizada')
                        ->maxLength(255),
                    TextInput::make('precio_unitario')
                        ->label('Precio unitario')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Bs')
                        ->visible(fn (Get $get) => $get('tipo') === 'personalizada')
                        ->required(fn (Get $get) => $get('../../origen') === 'directa' && $get('tipo') === 'personalizada'),
                    TextInput::make('cantidad')->numeric()->minValue(0.001)->step(0.001)->default(1)->required(),
                    TextInput::make('descuento')->numeric()->minValue(0)->default(0)->prefix('Bs'),
                ])
                ->columns(2)
                ->addActionLabel('Agregar línea')
                ->visible(fn (Get $get) => $get('origen') === 'directa')
                ->visibleOn('create'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->searchable()->sortable(),
                TextColumn::make('cliente')
                    ->label('Cliente')
                    ->state(fn (NotaVenta $record) => static::nombreCliente($record->cliente))
                    ->searchable(),
                TextColumn::make('ordenTrabajo.codigo')
                    ->label('Orden')
                    ->placeholder('—')
                    ->url(fn (NotaVenta $record) => $record->orden_trabajo_id
                        ? OrdenTrabajoResource::getUrl('edit', ['record' => $record->orden_trabajo_id])
                        : null),
                TextColumn::make('fecha_emision')->label('Fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('total')->money('BOB')->sortable(),
                TextColumn::make('monto_pagado')->label('Pagado')->money('BOB'),
                TextColumn::make('saldo')->money('BOB'),
                TextColumn::make('estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::ESTADOS[$state] ?? $state)
                    ->color(fn (string $state) => static::colorEstado($state)),
            ])
            ->filters([
                SelectFilter::make('estado')->options(self::ESTADOS),
                Filter::make('fecha_emision')
                    ->schema([
                        DatePicker::make('desde'),
                        DatePicker::make('hasta'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['desde'] ?? null, fn (Builder $q, $fecha) => $q->whereDate('fecha_emision', '>=', $fecha))
                        ->when($data['hasta'] ?? null, fn (Builder $q, $fecha) => $q->whereDate('fecha_emision', '<=', $fecha))),
            ])
            ->defaultSort('fecha_emision', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            LineasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotasVenta::route('/'),
            'create' => CreateNotaVenta::route('/create'),
            'edit' => EditNotaVenta::route('/{record}/edit'),
        ];
    }
}
