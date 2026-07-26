<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PermisoResource\Pages\CreatePermiso;
use App\Filament\Admin\Resources\PermisoResource\Pages\EditPermiso;
use App\Filament\Admin\Resources\PermisoResource\Pages\ListPermisos;
use App\Models\Permiso;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Catálogo global de permisos (002-roles-permisos/spec.md: "solo el super admin crea o edita
 * el catálogo de permisos"). No hay un permiso dedicado en el catálogo para esto — se gatea
 * directamente con esSuperAdmin(), no se inventa un slug nuevo. Sin acción de eliminar: los
 * catálogos se desactivan, no se borran (constitution.md §2).
 */
class PermisoResource extends Resource
{
    protected static ?string $model = Permiso::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Permisos';

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->esSuperAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

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
            TextInput::make('modulo')
                ->required()
                ->maxLength(100),
            TextInput::make('nombre')
                ->required()
                ->maxLength(150)
                ->live(onBlur: true)
                ->afterStateUpdated(function (string $state, callable $set, callable $get) {
                    if (blank($get('slug'))) {
                        $set('slug', Str::slug("{$get('modulo')}.{$state}", '.'));
                    }
                }),
            TextInput::make('slug')
                ->required()
                ->maxLength(150)
                ->unique(ignoreRecord: true),
            Textarea::make('descripcion')
                ->maxLength(1000)
                ->columnSpanFull(),
            Toggle::make('activo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('modulo')->sortable()->searchable(),
                TextColumn::make('nombre')->sortable()->searchable(),
                TextColumn::make('slug')->sortable()->searchable(),
                ToggleColumn::make('activo'),
            ])
            ->filters([
                SelectFilter::make('modulo')
                    ->options(fn () => Permiso::query()->distinct()->orderBy('modulo')->pluck('modulo', 'modulo')->all()),
            ])
            ->defaultSort('modulo');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermisos::route('/'),
            'create' => CreatePermiso::route('/create'),
            'edit' => EditPermiso::route('/{record}/edit'),
        ];
    }
}
