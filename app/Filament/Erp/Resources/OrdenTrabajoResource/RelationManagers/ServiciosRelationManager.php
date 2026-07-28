<?php

namespace App\Filament\Erp\Resources\OrdenTrabajoResource\RelationManagers;

use App\Actions\Ordenes\AgregarLineaServicioAction;
use App\Actions\Ordenes\CambiarEstadoLineaServicioAction;
use App\Exceptions\BusinessException;
use App\Models\OrdenTrabajoServicio;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Líneas de servicio de la orden (011-spec.md). Se crean únicamente vía
 * `AgregarLineaServicioAction` (snapshot de `precio_unitario`, validación de descuento vs. bruto)
 * — el `CreateAction` de esta tabla no hace `::create()` directo, lo intercepta con `using()`
 * igual que `CreateEmpleado` intercepta la creación de empleado (008). Sin `EditAction`: editar
 * `precio_unitario`/`subtotal` a mano rompería el snapshot y el CHECK de la BD.
 */
class ServiciosRelationManager extends RelationManager
{
    protected static string $relationship = 'lineasServicios';

    protected static ?string $title = 'Servicios';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('servicio_catalogo_id')
                ->label('Servicio')
                ->relationship('servicioCatalogo', 'nombre', fn ($query) => $query->activos())
                ->searchable()
                ->required(),
            TextInput::make('cantidad')->numeric()->minValue(1)->default(1)->required(),
            TextInput::make('descuento')->numeric()->minValue(0)->default(0)->prefix('Bs'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('servicio_catalogo_id')
            ->columns([
                TextColumn::make('servicioCatalogo.nombre')->label('Servicio'),
                TextColumn::make('cantidad'),
                TextColumn::make('precio_unitario')->label('Precio unitario')->money('BOB'),
                TextColumn::make('descuento')->money('BOB'),
                TextColumn::make('subtotal')->money('BOB'),
                TextColumn::make('estado')->badge()->color(fn (string $state) => match ($state) {
                    'REALIZADO' => 'success',
                    'ANULADO' => 'danger',
                    default => 'warning',
                }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data) {
                        try {
                            return app(AgregarLineaServicioAction::class)->execute(
                                orden: $this->getOwnerRecord(),
                                servicioCatalogoId: (int) $data['servicio_catalogo_id'],
                                cantidad: (int) ($data['cantidad'] ?? 1),
                                descuento: (float) ($data['descuento'] ?? 0),
                            );
                        } catch (BusinessException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();

                            throw (new Halt)->rollBackDatabaseTransaction();
                        }
                    })
                    ->visible(fn () => $this->getOwnerRecord()->estado !== 'ANULADA'),
            ])
            ->recordActions([
                Action::make('marcarRealizado')
                    ->label('Marcar realizado')
                    ->icon('heroicon-o-check')
                    ->visible(fn (OrdenTrabajoServicio $record) => $record->estado === 'PENDIENTE' && $this->getOwnerRecord()->estado !== 'ANULADA')
                    ->requiresConfirmation()
                    ->action(function (OrdenTrabajoServicio $record) {
                        try {
                            app(CambiarEstadoLineaServicioAction::class)->execute($record, 'REALIZADO');
                        } catch (BusinessException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()->title('Línea marcada como realizada.')->send();
                    }),
                Action::make('anularLinea')
                    ->label('Anular línea')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (OrdenTrabajoServicio $record) => $record->estado !== 'ANULADO' && $this->getOwnerRecord()->estado !== 'ANULADA')
                    ->requiresConfirmation()
                    ->action(function (OrdenTrabajoServicio $record) {
                        try {
                            app(CambiarEstadoLineaServicioAction::class)->execute($record, 'ANULADO');
                        } catch (BusinessException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()->title('Línea anulada.')->send();
                    }),
            ]);
    }
}
