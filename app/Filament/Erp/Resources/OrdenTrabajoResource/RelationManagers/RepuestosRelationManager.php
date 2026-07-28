<?php

namespace App\Filament\Erp\Resources\OrdenTrabajoResource\RelationManagers;

use App\Actions\Ordenes\AgregarLineaRepuestoAction;
use App\Actions\Ordenes\CambiarEstadoLineaRepuestoAction;
use App\Exceptions\BusinessException;
use App\Models\OrdenTrabajoRepuesto;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Líneas de repuesto de la orden (011-spec.md). Igual que `ServiciosRelationManager`, la creación
 * pasa por `AgregarLineaRepuestoAction` (snapshot de `precio_unitario`, sin tocar stock todavía).
 * "Entregar" descuenta stock real vía `CambiarEstadoLineaRepuestoAction`; si
 * `RegistrarMovimientoInventarioAction` (010) rechaza por stock insuficiente, se reconstruye el
 * toast con nombre de repuesto y disponible real (011-plan.md: "Stock insuficiente para
 * [Repuesto]. Disponible: X") en vez de mostrar el mensaje genérico de la Action de inventario.
 */
class RepuestosRelationManager extends RelationManager
{
    protected static string $relationship = 'lineasRepuestos';

    protected static ?string $title = 'Repuestos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('repuesto_id')
                ->label('Repuesto')
                ->relationship('repuesto', 'nombre', fn ($query) => $query->activos())
                ->searchable()
                ->required(),
            TextInput::make('cantidad')->numeric()->minValue(0.001)->step(0.001)->default(1)->required(),
            TextInput::make('descuento')->numeric()->minValue(0)->default(0)->prefix('Bs'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('repuesto_id')
            ->columns([
                TextColumn::make('repuesto.nombre')->label('Repuesto'),
                TextColumn::make('cantidad')->numeric(3),
                TextColumn::make('precio_unitario')->label('Precio unitario')->money('BOB'),
                TextColumn::make('descuento')->money('BOB'),
                TextColumn::make('subtotal')->money('BOB'),
                TextColumn::make('estado')->badge()->color(fn (string $state) => match ($state) {
                    'ENTREGADO' => 'success',
                    'ANULADO' => 'danger',
                    default => 'warning',
                }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data) {
                        try {
                            return app(AgregarLineaRepuestoAction::class)->execute(
                                orden: $this->getOwnerRecord(),
                                repuestoId: (int) $data['repuesto_id'],
                                cantidad: (float) ($data['cantidad'] ?? 1),
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
                Action::make('entregar')
                    ->label('Entregar')
                    ->icon('heroicon-o-truck')
                    ->visible(fn (OrdenTrabajoRepuesto $record) => $record->estado === 'PENDIENTE' && $this->getOwnerRecord()->estado !== 'ANULADA')
                    ->requiresConfirmation()
                    ->action(function (OrdenTrabajoRepuesto $record) {
                        try {
                            app(CambiarEstadoLineaRepuestoAction::class)->execute($record, 'ENTREGADO', Filament::auth()->id());
                        } catch (BusinessException $exception) {
                            if (Str::contains($exception->getMessage(), 'Stock insuficiente')) {
                                $repuesto = $record->repuesto()->first();
                                Notification::make()
                                    ->danger()
                                    ->title("Stock insuficiente para {$repuesto->nombre}. Disponible: {$repuesto->stock_actual}")
                                    ->send();

                                return;
                            }

                            Notification::make()->danger()->title($exception->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()->title('Repuesto entregado, stock descontado.')->send();
                    }),
                Action::make('anularLinea')
                    ->label('Anular línea')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (OrdenTrabajoRepuesto $record) => $record->estado !== 'ANULADO' && $this->getOwnerRecord()->estado !== 'ANULADA')
                    ->requiresConfirmation()
                    ->action(function (OrdenTrabajoRepuesto $record) {
                        try {
                            app(CambiarEstadoLineaRepuestoAction::class)->execute($record, 'ANULADO', Filament::auth()->id());
                        } catch (BusinessException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()->title('Línea anulada.')->send();
                    }),
            ]);
    }
}
