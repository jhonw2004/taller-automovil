<?php

namespace App\Filament\Admin\Resources;

use App\Actions\Solicitudes\AprobarSinCompletarSolicitudAction;
use App\Actions\Solicitudes\AprobarYCompletarSolicitudAction;
use App\Actions\Solicitudes\IniciarRevisionSolicitudAction;
use App\Actions\Solicitudes\RechazarSolicitudAction;
use App\Exceptions\BusinessException;
use App\Filament\Admin\Resources\SolicitudTallerResource\Pages\ListSolicitudesTaller;
use App\Models\SolicitudTaller;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Bandeja del super admin para 004-solicitud-alta-taller. Sin páginas create/edit propias: la
 * solicitud solo se crea desde el formulario público (`App\Http\Controllers\SolicitudTallerController`)
 * y solo cambia de estado a través de las Actions de transición (nunca editando columnas sueltas),
 * así que `canCreate()`/`canEdit()` son `false`. Tampoco hay `canDelete()`: constitution.md §2
 * prohíbe el borrado físico de solicitudes completadas o rechazadas, y no tiene sentido borrar
 * una pendiente en vez de rechazarla/cancelarla (trazabilidad completa).
 */
class SolicitudTallerResource extends Resource
{
    protected static ?string $model = SolicitudTaller::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Solicitudes de Alta';

    protected static ?string $slug = 'solicitudes-taller';

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->tienePermiso('solicitudes.ver') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
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
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('taller_nombre')->label('Taller')->searchable(),
                TextColumn::make('solicitante_nombre')->label('Solicitante')->searchable(),
                TextColumn::make('solicitante_email')->label('Email')->searchable(),
                TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'COMPLETADA' => 'success',
                        'APROBADA' => 'info',
                        'RECHAZADA' => 'danger',
                        'CANCELADA' => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('enviada_at')->label('Enviada')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('estado')->options([
                    'PENDIENTE' => 'Pendiente',
                    'EN_REVISION' => 'En revisión',
                    'APROBADA' => 'Aprobada',
                    'COMPLETADA' => 'Completada',
                    'RECHAZADA' => 'Rechazada',
                    'CANCELADA' => 'Cancelada',
                ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->schema([
                        TextEntry::make('taller_nombre')->label('Taller'),
                        TextEntry::make('taller_direccion')->label('Dirección')->placeholder('—'),
                        TextEntry::make('referencia')->label('Referencia')->placeholder('—'),
                        TextEntry::make('categoriaPrincipal.nombre')->label('Categoría')->placeholder('—'),
                        TextEntry::make('solicitante_nombre')->label('Solicitante'),
                        TextEntry::make('solicitante_email')->label('Email'),
                        TextEntry::make('solicitante_telefono')->label('Teléfono'),
                        TextEntry::make('lat')->label('Latitud')->placeholder('Sin ubicación'),
                        TextEntry::make('lon')->label('Longitud')->placeholder('Sin ubicación'),
                        TextEntry::make('comentario')->label('Comentario')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('motivo_rechazo')->label('Motivo de rechazo')->placeholder('—')->columnSpanFull(),
                        RepeatableEntry::make('historial')
                            ->label('Historial')
                            ->schema([
                                TextEntry::make('estado_nuevo')->label('Estado'),
                                TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                                TextEntry::make('observacion')->label('Observación')->placeholder('—')->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
                Action::make('iniciarRevision')
                    ->label('Iniciar revisión')
                    ->icon('heroicon-o-magnifying-glass')
                    ->visible(fn (SolicitudTaller $record) => $record->estado === 'PENDIENTE')
                    ->requiresConfirmation()
                    ->action(fn (SolicitudTaller $record) => static::ejecutar(
                        $record,
                        fn () => app(IniciarRevisionSolicitudAction::class)->execute($record, Filament::auth()->user()),
                        'Solicitud en revisión.',
                    )),
                Action::make('aprobarSinCompletar')
                    ->label('Aprobar (sin crear taller aún)')
                    ->color('warning')
                    ->icon('heroicon-o-check')
                    ->visible(fn (SolicitudTaller $record) => in_array($record->estado, ['PENDIENTE', 'EN_REVISION'], true))
                    ->requiresConfirmation()
                    ->action(fn (SolicitudTaller $record) => static::ejecutar(
                        $record,
                        fn () => app(AprobarSinCompletarSolicitudAction::class)->execute($record, Filament::auth()->user()),
                        'Solicitud aprobada.',
                    )),
                Action::make('aprobarYCrearTaller')
                    ->label('Aprobar y crear taller')
                    ->color('success')
                    ->icon('heroicon-o-building-storefront')
                    ->visible(fn (SolicitudTaller $record) => in_array($record->estado, ['PENDIENTE', 'EN_REVISION', 'APROBADA'], true))
                    ->fillForm(fn (SolicitudTaller $record) => [
                        'nombre' => $record->taller_nombre,
                        'direccion' => $record->taller_direccion,
                        'telefono' => $record->solicitante_telefono,
                        'email' => $record->solicitante_email,
                        'lat' => $record->lat,
                        'lon' => $record->lon,
                    ])
                    ->schema([
                        TextInput::make('nombre')->required()->maxLength(255),
                        TextInput::make('direccion')->maxLength(255),
                        TextInput::make('telefono')->tel()->maxLength(30),
                        TextInput::make('email')->email()->maxLength(255),
                        TextInput::make('lat')->label('Latitud')->numeric()->minValue(-90)->maxValue(90)->required(),
                        TextInput::make('lon')->label('Longitud')->numeric()->minValue(-180)->maxValue(180)->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(fn (SolicitudTaller $record, array $data) => static::ejecutar(
                        $record,
                        fn () => app(AprobarYCompletarSolicitudAction::class)->execute($record, $data, Filament::auth()->user()),
                        'Taller creado a partir de la solicitud.',
                    )),
                Action::make('rechazar')
                    ->label('Rechazar')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (SolicitudTaller $record) => in_array($record->estado, ['PENDIENTE', 'EN_REVISION', 'APROBADA'], true))
                    ->schema([
                        Textarea::make('motivo_rechazo')->label('Motivo del rechazo')->required()->maxLength(1000),
                    ])
                    ->requiresConfirmation()
                    ->action(fn (SolicitudTaller $record, array $data) => static::ejecutar(
                        $record,
                        fn () => app(RechazarSolicitudAction::class)->execute($record, $data['motivo_rechazo'], Filament::auth()->user()),
                        'Solicitud rechazada.',
                    )),
            ]);
    }

    protected static function ejecutar(SolicitudTaller $record, \Closure $callback, string $mensajeExito): void
    {
        try {
            $callback();
        } catch (BusinessException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            return;
        }

        Notification::make()->success()->title($mensajeExito)->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSolicitudesTaller::route('/'),
        ];
    }
}
