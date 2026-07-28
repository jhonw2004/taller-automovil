<?php

namespace App\Filament\Erp\Resources;

use App\Actions\Inventario\RegistrarMovimientoInventarioAction;
use App\Exceptions\BusinessException;
use App\Filament\Erp\Resources\RepuestoResource\Pages\CreateRepuesto;
use App\Filament\Erp\Resources\RepuestoResource\Pages\EditRepuesto;
use App\Filament\Erp\Resources\RepuestoResource\Pages\ListRepuestos;
use App\Models\Repuesto;
use BackedEnum;
use Filament\Actions\Action as RecordAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Repuestos del taller activo (010-inventario-repuestos/spec.md). No requiere
 * `getEloquentQuery()` manual: `Repuesto` usa `BelongsToTaller`. `stock_actual` nunca es editable
 * desde el formulario (se deriva de `inventario_movimientos`) — se muestra deshabilitado en
 * edición y se ajusta solo vía la Action "Ajustar Stock", que pasa por
 * `RegistrarMovimientoInventarioAction` (mismo camino que usará el consumo automático de
 * `011-ordenes-trabajo`).
 */
class RepuestoResource extends Resource
{
    protected static ?string $model = Repuesto::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog';

    protected static ?string $navigationLabel = 'Repuestos';

    protected static ?string $modelLabel = 'Repuesto';

    protected static ?string $slug = 'repuestos';

    protected static function tienePermiso(string $slug): bool
    {
        return Filament::auth()->user()?->tienePermiso($slug, session('taller_activo_id')) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::tienePermiso('repuestos.ver');
    }

    public static function canCreate(): bool
    {
        return static::tienePermiso('repuestos.crear');
    }

    /** @param Repuesto $record */
    public static function canEdit(Model $record): bool
    {
        return static::tienePermiso('repuestos.editar');
    }

    /** @param Repuesto $record */
    public static function canDelete(Model $record): bool
    {
        return static::tienePermiso('repuestos.eliminar');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('codigo')->required()->maxLength(50)->scopedUnique(),
            TextInput::make('nombre')->required()->maxLength(255),
            Textarea::make('descripcion')->maxLength(2000)->columnSpanFull(),
            TextInput::make('codigo_barras')->label('Código de barras')->maxLength(100)->scopedUnique(),
            Select::make('unidad_medida_id')
                ->label('Unidad de medida')
                ->relationship('unidadMedida', 'nombre', fn ($query) => $query->activos())
                ->searchable()
                ->preload(),
            TextInput::make('stock_actual')
                ->label('Stock actual')
                ->numeric()
                ->default(0)
                ->disabled()
                ->dehydrated(false)
                ->visibleOn('edit')
                ->helperText('Se deriva de los movimientos de inventario. Usa "Ajustar Stock" para cambiarlo.'),
            TextInput::make('stock_minimo')->label('Stock mínimo')->numeric()->minValue(0)->default(0),
            TextInput::make('precio_costo')->label('Precio costo')->numeric()->prefix('Bs')->minValue(0)->required()->default(0),
            TextInput::make('precio_venta')->label('Precio venta')->numeric()->prefix('Bs')->minValue(0)->required()->default(0),
            Toggle::make('activo')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->searchable()->sortable(),
                TextColumn::make('nombre')->searchable()->sortable(),
                TextColumn::make('stock_actual')
                    ->label('Stock')
                    ->numeric(3)
                    ->color(fn (Repuesto $record) => $record->stock_actual <= $record->stock_minimo ? 'danger' : null)
                    ->weight(fn (Repuesto $record) => $record->stock_actual <= $record->stock_minimo ? 'bold' : null)
                    ->sortable(),
                TextColumn::make('stock_minimo')->label('Mínimo')->numeric(3),
                TextColumn::make('precio_venta')->label('Precio venta')->money('BOB')->sortable(),
                IconColumn::make('activo')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('activo'),
            ])
            ->recordActions([
                RecordAction::make('ajustarStock')
                    ->label('Ajustar Stock')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->visible(fn () => static::tienePermiso('inventario.ajustar'))
                    ->schema([
                        Select::make('tipo_movimiento')
                            ->label('Tipo')
                            ->options([
                                'ENTRADA' => 'Entrada',
                                'SALIDA' => 'Salida',
                                'AJUSTE_POSITIVO' => 'Ajuste positivo',
                                'AJUSTE_NEGATIVO' => 'Ajuste negativo',
                            ])
                            ->required(),
                        TextInput::make('cantidad')->numeric()->minValue(0.001)->step(0.001)->required(),
                        Textarea::make('motivo')->required()->maxLength(500),
                    ])
                    ->requiresConfirmation()
                    ->action(function (Repuesto $record, array $data) {
                        try {
                            app(RegistrarMovimientoInventarioAction::class)->execute(
                                repuesto: $record,
                                tipoMovimiento: $data['tipo_movimiento'],
                                cantidad: (float) $data['cantidad'],
                                usuarioSistemaId: Filament::auth()->id(),
                                motivo: $data['motivo'],
                            );
                        } catch (BusinessException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()->title('Movimiento de inventario registrado.')->send();
                    }),
            ])
            ->defaultSort('nombre');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRepuestos::route('/'),
            'create' => CreateRepuesto::route('/create'),
            'edit' => EditRepuesto::route('/{record}/edit'),
        ];
    }
}
