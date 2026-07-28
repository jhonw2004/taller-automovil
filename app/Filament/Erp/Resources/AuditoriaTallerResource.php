<?php

namespace App\Filament\Erp\Resources;

use App\Filament\Erp\Resources\AuditoriaTallerResource\Pages\ListAuditoriaTalleres;
use App\Models\AuditoriaTaller;
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
 * Auditoría de cambios sensibles del taller activo (015-spec.md), escrita por
 * `App\Observers\TallerObserver`. Solo lectura.
 */
class AuditoriaTallerResource extends Resource
{
    protected static ?string $model = AuditoriaTaller::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Auditoría del Taller';

    protected static ?string $modelLabel = 'Cambio Auditado';

    protected static ?string $slug = 'auditoria-taller';

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

    /** @param AuditoriaTaller $record */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /** @param AuditoriaTaller $record */
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
                TextColumn::make('operacion')->badge()->color(fn (string $state) => match ($state) {
                    'INSERT' => 'success',
                    'DELETE' => 'danger',
                    default => 'info',
                }),
                TextColumn::make('usuarioSistema.username')->label('Usuario')->placeholder('—'),
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('operacion')->options([
                    'INSERT' => 'Creación',
                    'UPDATE' => 'Actualización',
                    'DELETE' => 'Eliminación',
                ]),
            ])
            ->recordActions([
                ViewAction::make()->schema([
                    TextEntry::make('operacion'),
                    TextEntry::make('usuarioSistema.username')->label('Usuario')->placeholder('—'),
                    TextEntry::make('datos_old')->label('Datos anteriores')->formatStateUsing(
                        fn (?array $state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : '—'
                    ),
                    TextEntry::make('datos_new')->label('Datos nuevos')->formatStateUsing(
                        fn (?array $state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : '—'
                    ),
                    TextEntry::make('lat_old')->label('Latitud anterior')->placeholder('—'),
                    TextEntry::make('lat_new')->label('Latitud nueva')->placeholder('—'),
                    TextEntry::make('lon_old')->label('Longitud anterior')->placeholder('—'),
                    TextEntry::make('lon_new')->label('Longitud nueva')->placeholder('—'),
                    TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditoriaTalleres::route('/'),
        ];
    }
}
