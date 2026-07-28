<?php

namespace App\Filament\Erp\Resources;

use App\Filament\Erp\Resources\MovimientoInventarioResource\Pages\ListMovimientosInventario;
use App\Models\InventarioMovimiento;
use App\Models\Repuesto;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Auditoría de movimientos de inventario del taller activo (010-inventario-repuestos/spec.md).
 * Solo lectura: los movimientos son append-only (constitution.md §2), se crean únicamente vía
 * `RegistrarMovimientoInventarioAction` (botón "Ajustar Stock" de `RepuestoResource` o, más
 * adelante, el consumo automático de `011-ordenes-trabajo`) — nunca desde este Resource.
 */
class MovimientoInventarioResource extends Resource
{
    protected static ?string $model = InventarioMovimiento::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Movimientos de Inventario';

    protected static ?string $modelLabel = 'Movimiento de Inventario';

    protected static ?string $slug = 'movimientos-inventario';

    protected static function tienePermiso(string $slug): bool
    {
        return Filament::auth()->user()?->tienePermiso($slug, session('taller_activo_id')) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::tienePermiso('inventario.ver');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** @param InventarioMovimiento $record */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /** @param InventarioMovimiento $record */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('repuesto.nombre')->label('Repuesto')->searchable(),
                TextColumn::make('tipo_movimiento')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'ENTRADA', 'AJUSTE_POSITIVO' => 'success',
                        'SALIDA', 'AJUSTE_NEGATIVO' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('cantidad')->numeric(3),
                TextColumn::make('stock_anterior')->label('Stock anterior')->numeric(3),
                TextColumn::make('stock_resultante')->label('Stock resultante')->numeric(3),
                TextColumn::make('motivo')->limit(40)->placeholder('—'),
                TextColumn::make('usuarioSistema.username')->label('Usuario')->placeholder('—'),
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('tipo_movimiento')->options([
                    'ENTRADA' => 'Entrada',
                    'SALIDA' => 'Salida',
                    'AJUSTE_POSITIVO' => 'Ajuste positivo',
                    'AJUSTE_NEGATIVO' => 'Ajuste negativo',
                ]),
                SelectFilter::make('repuesto_id')
                    ->label('Repuesto')
                    ->options(fn () => Repuesto::pluck('nombre', 'id')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMovimientosInventario::route('/'),
        ];
    }
}
