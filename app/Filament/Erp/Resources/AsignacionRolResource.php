<?php

namespace App\Filament\Erp\Resources;

use App\Filament\Erp\Resources\AsignacionRolResource\Pages\CreateAsignacionRol;
use App\Filament\Erp\Resources\AsignacionRolResource\Pages\ListAsignacionesRol;
use App\Models\AsignacionRol;
use App\Models\Rol;
use App\Models\UsuarioSistema;
use BackedEnum;
use Filament\Actions\Action as RecordAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Formulario de asignación de rol filtrado por el taller activo (002-roles-permisos/spec.md).
 * No hace `Eloquent::create()` directo: el registro se crea invocando `AsignarRolAction`
 * (ver `Pages\CreateAsignacionRol::handleRecordCreation()`), que ya tiene todas las
 * validaciones de ámbito. "Desactivar" es un simple `update(['activo' => false])` — no amerita
 * una Action nueva, no hay regla de negocio adicional en desactivar.
 */
class AsignacionRolResource extends Resource
{
    protected static ?string $model = AsignacionRol::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Asignación de roles';

    protected static ?string $slug = 'asignaciones-rol';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('taller_id', session('taller_activo_id'));
    }

    protected static function tienePermiso(): bool
    {
        return Filament::auth()->user()?->tienePermiso('usuarios.gestionar', session('taller_activo_id')) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::tienePermiso();
    }

    public static function canCreate(): bool
    {
        return static::tienePermiso();
    }

    /** @param AsignacionRol $record */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /** @param AsignacionRol $record */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('usuario_sistema_id')
                ->label('Usuario')
                ->options(fn () => UsuarioSistema::query()
                    ->where('activo', true)
                    ->get()
                    ->mapWithKeys(fn (UsuarioSistema $u) => [$u->id => trim("{$u->nombre} {$u->apellido}")." ({$u->username})"]))
                ->searchable()
                ->required(),
            Select::make('rol_id')
                ->label('Rol')
                ->options(fn () => Rol::query()
                    ->where('activo', true)
                    ->where(fn (Builder $q) => $q->whereNull('taller_id')->orWhere('taller_id', session('taller_activo_id')))
                    ->pluck('nombre', 'id'))
                ->searchable()
                ->required(),
            DatePicker::make('vigente_desde')->default(now()->toDateString())->required(),
            DatePicker::make('vigente_hasta')->label('Vigente hasta (opcional)'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('usuarioSistema.nombre')
                    ->label('Usuario')
                    ->formatStateUsing(fn (AsignacionRol $record) => trim("{$record->usuarioSistema->nombre} {$record->usuarioSistema->apellido}"))
                    ->searchable(),
                TextColumn::make('rol.nombre')->label('Rol')->sortable(),
                TextColumn::make('vigente_desde')->date(),
                TextColumn::make('vigente_hasta')->date()->placeholder('Sin fecha límite'),
                IconColumn::make('activo')->boolean(),
            ])
            ->recordActions([
                RecordAction::make('desactivar')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->visible(fn (AsignacionRol $record) => $record->activo)
                    ->action(fn (AsignacionRol $record) => $record->update(['activo' => false])),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAsignacionesRol::route('/'),
            'create' => CreateAsignacionRol::route('/create'),
        ];
    }
}
