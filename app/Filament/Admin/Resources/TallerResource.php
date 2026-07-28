<?php

namespace App\Filament\Admin\Resources;

use App\Actions\Talleres\CambiarEstadoTallerAction;
use App\Actions\Talleres\CambiarPropietarioTallerAction;
use App\Exceptions\BusinessException;
use App\Filament\Admin\Resources\TallerResource\Pages\ListTalleres;
use App\Models\Taller;
use App\Models\UsuarioSistema;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Tabla global de todos los talleres para Super Admin (003-gestion-talleres/spec.md). No usa
 * `Taller::query()` filtrado por `BelongsToTaller` (`Taller` no usa ese trait, es la raíz del
 * tenant) — se ve completa a propósito. Sin página de edición propia: los datos del taller los
 * edita su dueño desde `/erp` (ver `App\Filament\Erp\Resources\TallerResource`); aquí solo se
 * exponen las acciones de plataforma (suspender, cambiar propietario, soft delete/restore).
 */
class TallerResource extends Resource
{
    protected static ?string $model = Taller::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Talleres';

    protected static ?string $slug = 'talleres';

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->tienePermiso('admin.talleres.ver') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /** @param Taller $record */
    public static function canDelete(Model $record): bool
    {
        return Filament::auth()->user()?->tienePermiso('admin.talleres.suspender') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->sortable()->searchable(),
                TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'ACTIVO' => 'success',
                        'SUSPENDIDO' => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('visible_en_mapa')->boolean(),
                TextColumn::make('calificacion_promedio')->label('Calificación'),
                TextColumn::make('propietario.nombre')->label('Propietario')->placeholder('Sin asignar'),
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn (?string $state) => $state ? 'Eliminado' : '—'),
            ])
            ->filters([
                SelectFilter::make('estado')->options([
                    'ACTIVO' => 'Activo',
                    'INACTIVO' => 'Inactivo',
                    'SUSPENDIDO' => 'Suspendido',
                ]),
                TernaryFilter::make('visible_en_mapa'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('suspender')
                    ->label('Suspender')
                    ->color('danger')
                    ->visible(fn (Taller $record) => $record->estado !== 'SUSPENDIDO'
                        && (Filament::auth()->user()?->tienePermiso('admin.talleres.suspender') ?? false))
                    ->requiresConfirmation()
                    ->action(fn (Taller $record) => static::cambiarEstado($record, 'SUSPENDIDO')),
                Action::make('activar')
                    ->label('Activar')
                    ->color('success')
                    ->visible(fn (Taller $record) => $record->estado === 'SUSPENDIDO'
                        && (Filament::auth()->user()?->tienePermiso('admin.talleres.suspender') ?? false))
                    ->requiresConfirmation()
                    ->action(fn (Taller $record) => static::cambiarEstado($record, 'ACTIVO')),
                Action::make('cambiarPropietario')
                    ->label('Cambiar propietario')
                    ->visible(fn () => Filament::auth()->user()?->tienePermiso('admin.talleres.cambiar_propietario') ?? false)
                    ->schema([
                        Select::make('nuevo_propietario_id')
                            ->label('Nuevo propietario')
                            ->options(fn () => UsuarioSistema::query()->where('activo', true)
                                ->get()
                                ->mapWithKeys(fn (UsuarioSistema $u) => [$u->id => trim("{$u->nombre} {$u->apellido}")." ({$u->username})"]))
                            ->searchable()
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function (Taller $record, array $data) {
                        try {
                            app(CambiarPropietarioTallerAction::class)->execute(
                                $record,
                                UsuarioSistema::findOrFail($data['nuevo_propietario_id']),
                                Filament::auth()->user(),
                            );
                        } catch (BusinessException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()->title('Propietario actualizado.')->send();
                    }),
                // Advertencia con conteo real antes de soft-delete (003-gestion-talleres/spec.md:
                // la operación no se bloquea aunque haya entidades hijas activas, solo se advierte).
                DeleteAction::make()
                    ->modalDescription(fn (Taller $record) => static::describirEntidadesHijas($record)),
                RestoreAction::make(),
            ]);
    }

    protected static function describirEntidadesHijas(Taller $record): string
    {
        $conteos = collect($record->contarEntidadesHijasActivas())->filter(fn (int $cantidad) => $cantidad > 0);

        if ($conteos->isEmpty()) {
            return 'Este taller no tiene entidades hijas activas registradas. No se bloqueará la eliminación de todos modos.';
        }

        $detalle = $conteos->map(fn (int $cantidad, string $etiqueta) => "{$cantidad} {$etiqueta}")->implode(', ');

        return "Este taller tiene: {$detalle}. No se bloqueará la eliminación, pero esos datos quedarán ocultos del ERP hasta que se restaure el taller.";
    }

    protected static function cambiarEstado(Taller $record, string $nuevoEstado): void
    {
        try {
            app(CambiarEstadoTallerAction::class)->execute($record, $nuevoEstado, Filament::auth()->user());
        } catch (BusinessException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            return;
        }

        Notification::make()->success()->title('Estado actualizado.')->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTalleres::route('/'),
        ];
    }
}
