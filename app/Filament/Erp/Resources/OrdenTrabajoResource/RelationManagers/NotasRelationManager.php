<?php

namespace App\Filament\Erp\Resources\OrdenTrabajoResource\RelationManagers;

use App\Actions\Ordenes\AgregarNotaOrdenAction;
use App\Exceptions\BusinessException;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Notas de la orden (011-spec.md): `INTERNA` (solo ERP) o `PUBLICA` (reservada para un futuro
 * portal de cliente, sin efecto visible en el MVP). Sin `EditAction`/`DeleteAction`: una nota es
 * un registro de lo que se dijo en su momento, no se corrige después.
 */
class NotasRelationManager extends RelationManager
{
    protected static string $relationship = 'notas';

    protected static ?string $title = 'Notas';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('tipo')->options(['INTERNA' => 'Interna', 'PUBLICA' => 'Pública'])->default('INTERNA')->required(),
            Textarea::make('nota')->required()->maxLength(2000)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nota')
            ->columns([
                TextColumn::make('tipo')->badge()->color(fn (string $state) => $state === 'PUBLICA' ? 'info' : 'gray'),
                TextColumn::make('nota')->limit(60),
                TextColumn::make('usuarioSistema.username')->label('Usuario')->placeholder('—'),
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data) {
                        try {
                            return app(AgregarNotaOrdenAction::class)->execute(
                                orden: $this->getOwnerRecord(),
                                tipo: $data['tipo'],
                                nota: $data['nota'],
                                usuarioSistemaId: Filament::auth()->id(),
                            );
                        } catch (BusinessException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();

                            throw (new Halt)->rollBackDatabaseTransaction();
                        }
                    })
                    ->visible(fn () => $this->getOwnerRecord()->estado !== 'ANULADA'),
            ])
            ->recordActions([])
            ->defaultSort('created_at', 'desc');
    }
}
