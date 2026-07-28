<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UnidadMedidaResource\Pages\CreateUnidadMedida;
use App\Filament\Admin\Resources\UnidadMedidaResource\Pages\EditUnidadMedida;
use App\Filament\Admin\Resources\UnidadMedidaResource\Pages\ListUnidadesMedida;
use App\Models\UnidadMedida;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo global de unidades de medida (010-inventario-repuestos/spec.md), no por taller.
 * No estaba en la lista de Resources de `010-plan.md` (solo lista Repuesto/Proveedor/Movimiento),
 * pero sin esta pantalla `repuestos.unidad_medida_id` no tendría ningún dato que seleccionar —
 * misma clase de extensión ya documentada en `008-empleados-usuarios-erp` (extender el criterio
 * del plan cuando se sigue directamente de él). Mismo patrón que `CategoriaResource`: catálogo
 * global, gateado con `esSuperAdmin()` directo, sin permiso dedicado en el catálogo de `002`.
 */
class UnidadMedidaResource extends Resource
{
    protected static ?string $model = UnidadMedida::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Unidades de Medida';

    protected static ?string $slug = 'unidades-medida';

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->esSuperAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    /** @param UnidadMedida $record */
    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->required()->maxLength(100),
            TextInput::make('simbolo')->label('Símbolo')->required()->maxLength(10),
            TextInput::make('descripcion')->maxLength(255),
            Toggle::make('activo')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->sortable()->searchable(),
                TextColumn::make('simbolo')->label('Símbolo')->sortable()->searchable(),
                ToggleColumn::make('activo'),
            ])
            ->defaultSort('nombre');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnidadesMedida::route('/'),
            'create' => CreateUnidadMedida::route('/create'),
            'edit' => EditUnidadMedida::route('/{record}/edit'),
        ];
    }
}
