<?php

namespace App\Filament\Erp\Resources;

use App\Actions\Identidad\ActivarDesactivarUsuarioSistemaAction;
use App\Actions\Identidad\RestablecerPasswordUsuarioAction;
use App\Exceptions\BusinessException;
use App\Filament\Erp\Resources\UsuarioResource\Pages\ListUsuarios;
use App\Models\UsuarioSistema;
use BackedEnum;
use Filament\Actions\Action as RecordAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Usuarios del sistema con una asignación de rol vigente en el taller activo
 * (008-empleados-usuarios-erp/spec.md). No es un CRUD: la creación de cuenta viene siempre
 * desde `EmpleadoResource` (o del flujo de aprobación de `004` para el owner); aquí solo se
 * restablece contraseña o se activa/desactiva, ambas vía Action dedicada que valida
 * pertenencia al taller activo.
 */
class UsuarioResource extends Resource
{
    protected static ?string $model = UsuarioSistema::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $slug = 'usuarios';

    public static function getEloquentQuery(): Builder
    {
        $tallerId = session('taller_activo_id');
        $hoy = now()->toDateString();

        return parent::getEloquentQuery()
            ->whereHas('asignacionesRol', function (Builder $query) use ($tallerId, $hoy) {
                $query->where('taller_id', $tallerId)
                    ->where('activo', true)
                    ->where('vigente_desde', '<=', $hoy)
                    ->where(fn (Builder $q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $hoy));
            });
    }

    protected static function tienePermiso(string $slug): bool
    {
        return Filament::auth()->user()?->tienePermiso($slug, session('taller_activo_id')) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::tienePermiso('usuarios.ver');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** @param UsuarioSistema $record */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /** @param UsuarioSistema $record */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')->searchable()->sortable(),
                TextColumn::make('nombre_completo')
                    ->label('Nombre')
                    ->state(fn (UsuarioSistema $record) => trim("{$record->nombre} {$record->apellido}"))
                    ->searchable(['nombre', 'apellido']),
                TextColumn::make('roles')
                    ->label('Roles en este taller')
                    ->state(fn (UsuarioSistema $record) => $record->asignacionesVigentes()
                        ->where('taller_id', session('taller_activo_id'))
                        ->map(fn ($asignacion) => $asignacion->rol->nombre)
                        ->implode(', ')),
                TextColumn::make('ultimo_acceso_at')->label('Último acceso')->dateTime()->placeholder('Nunca'),
                IconColumn::make('activo')->boolean(),
            ])
            ->recordActions([
                RecordAction::make('restablecer_password')
                    ->label('Restablecer contraseña')
                    ->icon('heroicon-o-key')
                    ->visible(fn () => static::tienePermiso('usuarios.gestionar'))
                    ->requiresConfirmation()
                    ->action(function (UsuarioSistema $record) {
                        try {
                            $passwordTemporal = app(RestablecerPasswordUsuarioAction::class)
                                ->execute($record, session('taller_activo_id'));
                        } catch (BusinessException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();

                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title('Contraseña restablecida')
                            ->body("Nueva contraseña temporal: {$passwordTemporal} (guárdala ahora, no se volverá a mostrar).")
                            ->persistent()
                            ->send();
                    }),
                RecordAction::make('desactivar')
                    ->color('danger')
                    ->visible(fn (UsuarioSistema $record) => $record->activo && static::tienePermiso('usuarios.gestionar'))
                    ->requiresConfirmation()
                    ->action(fn (UsuarioSistema $record) => static::cambiarActivo($record, false)),
                RecordAction::make('activar')
                    ->visible(fn (UsuarioSistema $record) => ! $record->activo && static::tienePermiso('usuarios.gestionar'))
                    ->requiresConfirmation()
                    ->action(fn (UsuarioSistema $record) => static::cambiarActivo($record, true)),
            ])
            ->defaultSort('username');
    }

    protected static function cambiarActivo(UsuarioSistema $record, bool $activo): void
    {
        try {
            app(ActivarDesactivarUsuarioSistemaAction::class)->execute($record, session('taller_activo_id'), $activo);
        } catch (BusinessException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsuarios::route('/'),
        ];
    }
}
