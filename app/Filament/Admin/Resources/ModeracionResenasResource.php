<?php

namespace App\Filament\Admin\Resources;

use App\Actions\Resenas\ModerarResenaAction;
use App\Exceptions\BusinessException;
use App\Filament\Admin\Resources\ModeracionResenasResource\Pages\ListModeracionResenas;
use App\Models\Resena;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Moderación de reseñas (006-resenas-favoritos/spec.md): solo cambia `estado`, nunca edita
 * calificación/comentario (esos son del autor) ni borra físicamente — `canCreate()`/`canEdit()`/
 * `canDelete()` en `false`, igual patrón que `SolicitudTallerResource` de 004.
 */
class ModeracionResenasResource extends Resource
{
    protected static ?string $model = Resena::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Moderación de Reseñas';

    protected static ?string $slug = 'moderacion-resenas';

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->tienePermiso('moderacion.resenas') ?? false;
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
                TextColumn::make('taller.nombre')->label('Taller')->searchable(),
                TextColumn::make('usuario.nombre')->label('Usuario')->searchable(),
                TextColumn::make('calificacion')->label('Calificación')->badge(),
                TextColumn::make('comentario')->label('Comentario')->limit(60)->placeholder('—'),
                TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'PUBLICADA' => 'success',
                        'REPORTADA' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('estado')->options([
                    'PUBLICADA' => 'Publicada',
                    'OCULTA' => 'Oculta',
                    'REPORTADA' => 'Reportada',
                ]),
            ])
            ->recordActions([
                Action::make('ocultar')
                    ->label('Ocultar')
                    ->color('gray')
                    ->icon('heroicon-o-eye-slash')
                    ->visible(fn (Resena $record) => $record->estado !== 'OCULTA')
                    ->requiresConfirmation()
                    ->action(fn (Resena $record) => static::moderar($record, 'OCULTA')),
                Action::make('marcarReportada')
                    ->label('Marcar como reportada')
                    ->color('danger')
                    ->icon('heroicon-o-flag')
                    ->visible(fn (Resena $record) => $record->estado !== 'REPORTADA')
                    ->requiresConfirmation()
                    ->action(fn (Resena $record) => static::moderar($record, 'REPORTADA')),
                Action::make('publicar')
                    ->label('Publicar')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn (Resena $record) => $record->estado !== 'PUBLICADA')
                    ->requiresConfirmation()
                    ->action(fn (Resena $record) => static::moderar($record, 'PUBLICADA')),
            ]);
    }

    private static function moderar(Resena $record, string $estado): void
    {
        try {
            app(ModerarResenaAction::class)->execute($record, $estado, Filament::auth()->user());
        } catch (BusinessException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            return;
        }

        Notification::make()->success()->title('Reseña actualizada.')->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModeracionResenas::route('/'),
        ];
    }
}
