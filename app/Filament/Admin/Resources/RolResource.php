<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RolResource\Pages\CreateRol;
use App\Filament\Admin\Resources\RolResource\Pages\EditRol;
use App\Filament\Admin\Resources\RolResource\Pages\ListRols;
use App\Models\Permiso;
use App\Models\Rol;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Roles globales (`taller_id IS NULL`), administrados por Super Admin (002-roles-permisos/spec.md).
 * Gate con el permiso dedicado del catálogo `admin.roles.gestionar` — a diferencia de
 * PermisoResource/CategoriaResource, aquí sí existe un slug de permiso propio para esto.
 */
class RolResource extends Resource
{
    protected static ?string $model = Rol::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Roles globales';

    protected static ?string $slug = 'roles';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNull('taller_id');
    }

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->tienePermiso('admin.roles.gestionar') ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    /** @param Rol $record */
    public static function canEdit(Model $record): bool
    {
        return static::canViewAny() && ! $record->es_sistema;
    }

    /** @param Rol $record */
    public static function canDelete(Model $record): bool
    {
        return static::canViewAny() && ! $record->es_sistema;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->required()->maxLength(100),
            TextInput::make('slug')->required()->maxLength(120)->unique(ignoreRecord: true),
            Textarea::make('descripcion')->maxLength(1000)->columnSpanFull(),
            Toggle::make('es_sistema')
                ->label('Rol de sistema (fijo, no editable desde este panel)')
                ->default(false)
                ->disabled(),
            Toggle::make('activo')->default(true),
            CheckboxList::make('permisos')
                ->relationship(
                    titleAttribute: 'nombre',
                    modifyQueryUsing: fn (Builder $query) => $query->where('activo', true),
                )
                ->searchable()
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->sortable()->searchable(),
                TextColumn::make('slug')->sortable()->searchable(),
                IconColumn::make('es_sistema')->boolean()->label('Sistema'),
                ToggleColumn::make('activo'),
            ])
            ->defaultSort('nombre');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRols::route('/'),
            'create' => CreateRol::route('/create'),
            'edit' => EditRol::route('/{record}/edit'),
        ];
    }
}
