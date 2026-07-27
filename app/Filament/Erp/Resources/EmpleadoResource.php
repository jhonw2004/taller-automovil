<?php

namespace App\Filament\Erp\Resources;

use App\Filament\Erp\Resources\EmpleadoResource\Pages\CreateEmpleado;
use App\Filament\Erp\Resources\EmpleadoResource\Pages\EditEmpleado;
use App\Filament\Erp\Resources\EmpleadoResource\Pages\ListEmpleados;
use App\Models\Empleado;
use App\Models\Rol;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Empleados del taller activo (008-empleados-usuarios-erp/spec.md). No requiere
 * `getEloquentQuery()` manual (ver `ClienteResource`): `Empleado` usa `BelongsToTaller`.
 *
 * El toggle "Tiene acceso al sistema" no es una columna de `Empleado` — se intercepta en
 * `Pages\CreateEmpleado`/`Pages\EditEmpleado` antes de llegar a `Empleado::create()`/`update()`,
 * disparando `CrearEmpleadoConAccesoAction`/`VincularAccesoEmpleadoAction` en su lugar.
 */
class EmpleadoResource extends Resource
{
    protected static ?string $model = Empleado::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Empleados';

    protected static ?string $slug = 'empleados';

    protected static function tienePermiso(string $slug): bool
    {
        return Filament::auth()->user()?->tienePermiso($slug, session('taller_activo_id')) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::tienePermiso('empleados.ver');
    }

    public static function canCreate(): bool
    {
        return static::tienePermiso('empleados.crear');
    }

    /** @param Empleado $record */
    public static function canEdit(Model $record): bool
    {
        return static::tienePermiso('empleados.editar');
    }

    /** @param Empleado $record */
    public static function canDelete(Model $record): bool
    {
        return static::tienePermiso('empleados.eliminar');
    }

    protected static function rolesDisponibles(): array
    {
        return Rol::query()
            ->where('activo', true)
            ->where(fn (Builder $q) => $q->whereNull('taller_id')->orWhere('taller_id', session('taller_activo_id')))
            ->pluck('nombre', 'id')
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('codigo')->required()->maxLength(50)->scopedUnique(),
            TextInput::make('nombre')->required()->maxLength(255),
            TextInput::make('apellido')->maxLength(255),
            TextInput::make('cargo')->maxLength(255),
            TextInput::make('telefono')->tel()->maxLength(30),
            TextInput::make('email')->email()->maxLength(255),
            DatePicker::make('fecha_ingreso'),
            Toggle::make('activo')->default(true),

            Toggle::make('tiene_acceso')
                ->label('Tiene acceso al sistema')
                ->live()
                ->default(fn (?Empleado $record) => $record?->usuario_sistema_id !== null)
                ->disabled(fn (?Empleado $record) => $record?->usuario_sistema_id !== null)
                ->helperText(fn (?Empleado $record) => $record?->usuario_sistema_id
                    ? "Ya vinculado a {$record->usuarioSistema->username}."
                    : 'Al guardar, se crea un usuario del sistema con contraseña temporal.'),
            TextInput::make('username')
                ->maxLength(50)
                ->required(fn (callable $get) => $get('tiene_acceso'))
                ->visible(fn (callable $get, ?Empleado $record) => $get('tiene_acceso') && ! $record?->usuario_sistema_id),
            Select::make('rol_id')
                ->label('Rol')
                ->options(fn () => static::rolesDisponibles())
                ->searchable()
                ->required(fn (callable $get) => $get('tiene_acceso'))
                ->visible(fn (callable $get, ?Empleado $record) => $get('tiene_acceso') && ! $record?->usuario_sistema_id),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->searchable()->sortable(),
                TextColumn::make('nombre_completo')
                    ->label('Nombre')
                    ->state(fn (Empleado $record) => trim("{$record->nombre} {$record->apellido}"))
                    ->searchable(['nombre', 'apellido']),
                TextColumn::make('cargo'),
                TextColumn::make('usuarioSistema.username')
                    ->label('Usuario vinculado')
                    ->placeholder('Sin acceso'),
                IconColumn::make('activo')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('activo'),
            ])
            ->defaultSort('nombre');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmpleados::route('/'),
            'create' => CreateEmpleado::route('/create'),
            'edit' => EditEmpleado::route('/{record}/edit'),
        ];
    }
}
