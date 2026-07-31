<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AuditoriaAccesoResource\Pages\ListAuditoriaAccesos;
use App\Models\AuditoriaAcceso;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Auditoría de accesos de TODOS los usuarios (sistema y marketplace), sin filtro por taller
 * (015-spec.md: "super admin consulta auditoría global"). Solo lectura, se escribe únicamente vía
 * `App\Actions\Auditoria\RegistrarAccesoAuditoriaAction`. Nunca expone `password_hash`
 * (constitution.md §7) — la tabla ni siquiera tiene esa columna.
 */
class AuditoriaAccesoResource extends Resource
{
    protected static ?string $model = AuditoriaAcceso::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Auditoría de Accesos';

    protected static ?string $modelLabel = 'Acceso Auditado';

    protected static ?string $slug = 'auditoria-accesos';

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->tienePermiso('admin.auditoria.ver') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** @param AuditoriaAcceso $record */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /** @param AuditoriaAcceso $record */
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
                TextColumn::make('identificador')->label('Usuario')->placeholder('—')->searchable(),
                TextColumn::make('tipo_acceso')->label('Tipo')->badge(),
                TextColumn::make('resultado')->badge()->color(fn (string $state) => match ($state) {
                    'EXITOSO' => 'success',
                    'FALLIDO', 'DENEGADO' => 'danger',
                    default => 'gray',
                }),
                TextColumn::make('ip')->label('IP')->placeholder('—'),
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('tipo_acceso')->options([
                    'LOGIN' => 'Inicio de sesión',
                    'LOGOUT' => 'Cierre de sesión',
                    'PASSWORD_CHANGE' => 'Cambio de contraseña',
                    'FAILED_LOGIN' => 'Inicio de sesión fallido',
                ]),
                SelectFilter::make('resultado')->options([
                    'EXITOSO' => 'Exitoso',
                    'FALLIDO' => 'Fallido',
                    'DENEGADO' => 'Denegado',
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditoriaAccesos::route('/'),
        ];
    }
}
