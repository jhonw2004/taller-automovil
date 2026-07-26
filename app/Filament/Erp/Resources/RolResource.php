<?php

namespace App\Filament\Erp\Resources;

use App\Filament\Erp\Resources\RolResource\Pages\CreateRol;
use App\Filament\Erp\Resources\RolResource\Pages\EditRol;
use App\Filament\Erp\Resources\RolResource\Pages\ListRols;
use App\Models\Rol;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
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
 * Roles personalizados del taller activo (`taller_id = session('taller_activo_id')`),
 * administrados por el owner/admin del taller (002-roles-permisos/spec.md). Los roles de
 * sistema (MECANICO, CAJERO, etc.) tienen `taller_id = NULL` y por lo tanto nunca aparecen
 * aquí — su gestión de asignación vive en AsignacionRolResource, no en este listado.
 */
class RolResource extends Resource
{
    protected static ?string $model = Rol::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Roles del taller';

    protected static ?string $slug = 'roles';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('taller_id', session('taller_activo_id'));
    }

    protected static function tienePermiso(string $slug): bool
    {
        return Filament::auth()->user()?->tienePermiso($slug, session('taller_activo_id')) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::tienePermiso('roles.ver');
    }

    public static function canCreate(): bool
    {
        return static::tienePermiso('roles.crear');
    }

    /** @param Rol $record */
    public static function canEdit(Model $record): bool
    {
        return static::tienePermiso('roles.editar') && ! $record->es_sistema;
    }

    /** @param Rol $record */
    public static function canDelete(Model $record): bool
    {
        return static::tienePermiso('roles.eliminar') && ! $record->es_sistema;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // Siempre el taller activo, nunca editable desde el formulario: evita que un
            // admin de taller cree o mueva un rol hacia otro taller.
            Hidden::make('taller_id')->default(fn () => session('taller_activo_id')),
            Hidden::make('es_sistema')->default(false),
            TextInput::make('nombre')->required()->maxLength(100),
            // scopedUnique(), no unique(): la unicidad real es (taller_id, slug) — usa la misma
            // query ya acotada al taller activo por getEloquentQuery(), no todos los roles.
            TextInput::make('slug')->required()->maxLength(120)->scopedUnique(),
            Textarea::make('descripcion')->maxLength(1000)->columnSpanFull(),
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
