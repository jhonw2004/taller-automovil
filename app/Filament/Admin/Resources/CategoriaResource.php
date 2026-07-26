<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CategoriaResource\Pages\CreateCategoria;
use App\Filament\Admin\Resources\CategoriaResource\Pages\EditCategoria;
use App\Filament\Admin\Resources\CategoriaResource\Pages\ListCategorias;
use App\Models\Categoria;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo global de categorías de taller (003-gestion-talleres). Sin permiso dedicado en el
 * catálogo — igual que PermisoResource, se gatea con esSuperAdmin() directo. `slug` no es un
 * campo del form: `Categoria` usa `HasSlug` (spatie/laravel-sluggable), se genera solo desde
 * `nombre` al guardar.
 */
class CategoriaResource extends Resource
{
    protected static ?string $model = Categoria::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Categorías';

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
            TextInput::make('nombre')->required()->maxLength(150),
            Textarea::make('descripcion')->maxLength(1000)->columnSpanFull(),
            Toggle::make('activo')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->sortable()->searchable(),
                TextColumn::make('slug')->sortable()->searchable(),
                ToggleColumn::make('activo'),
            ])
            ->defaultSort('nombre');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategorias::route('/'),
            'create' => CreateCategoria::route('/create'),
            'edit' => EditCategoria::route('/{record}/edit'),
        ];
    }
}
