<?php

namespace App\Filament\Erp\Resources;

use App\Filament\Erp\Resources\AuditoriaEventoResource\Pages\ListAuditoriaEventos;
use App\Models\AuditoriaEvento;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Auditoría de eventos de negocio del taller activo (015-spec.md). Solo lectura: se escribe
 * únicamente vía `App\Actions\Auditoria\RegistrarEventoAuditoriaAction`. `AuditoriaEvento` no usa
 * `BelongsToTaller` (a diferencia de `InventarioMovimiento`) porque `/admin` necesita ver eventos
 * de todos los talleres — el filtrado por taller activo se hace a mano en `getEloquentQuery()`,
 * mismo patrón que `RolResource`/`TallerResource` de `/erp`.
 */
class AuditoriaEventoResource extends Resource
{
    protected static ?string $model = AuditoriaEvento::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Auditoría de Eventos';

    protected static ?string $modelLabel = 'Evento de Auditoría';

    protected static ?string $slug = 'auditoria-eventos';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('taller_id', session('taller_activo_id'));
    }

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->tienePermiso('auditoria.ver', session('taller_activo_id')) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** @param AuditoriaEvento $record */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /** @param AuditoriaEvento $record */
    public static function canDelete(Model $record): bool
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
            ->columns([
                TextColumn::make('evento')->badge()->searchable(),
                TextColumn::make('usuarioSistema.username')->label('Usuario')->placeholder('Sistema'),
                TextColumn::make('entidad_tipo')->label('Entidad')->formatStateUsing(
                    fn (?string $state) => $state ? class_basename($state) : '—'
                ),
                TextColumn::make('entidad_id')->label('ID')->placeholder('—'),
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('evento')->options(
                    fn () => AuditoriaEvento::query()->where('taller_id', session('taller_activo_id'))
                        ->distinct()->pluck('evento', 'evento')
                ),
            ])
            ->recordActions([
                ViewAction::make()->schema([
                    TextEntry::make('evento'),
                    TextEntry::make('usuarioSistema.username')->label('Usuario')->placeholder('Sistema'),
                    TextEntry::make('entidad_tipo')->label('Entidad'),
                    TextEntry::make('entidad_id')->label('ID entidad'),
                    TextEntry::make('datos')->label('Datos')->formatStateUsing(
                        fn (?array $state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : '—'
                    ),
                    TextEntry::make('ip')->label('IP')->placeholder('—'),
                    TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditoriaEventos::route('/'),
        ];
    }
}
