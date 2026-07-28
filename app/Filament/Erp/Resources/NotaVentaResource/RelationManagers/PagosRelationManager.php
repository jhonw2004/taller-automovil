<?php

namespace App\Filament\Erp\Resources\NotaVentaResource\RelationManagers;

use App\Actions\Pagos\AnularPagoAction;
use App\Actions\Pagos\RegistrarPagoAction;
use App\Exceptions\BusinessException;
use App\Models\MetodoPago;
use App\Models\NotaVenta;
use App\Models\Pago;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Component as Livewire;

/**
 * Historial de pagos de la nota (013-spec.md/plan.md). El modal "Registrar Pago" pasa por
 * `RegistrarPagoAction` (nunca `Pago::create()` directo, mismo patrón `using()`+`Halt` que
 * `RepuestosRelationManager`/`NotasRelationManager` de 011); "Anular pago" pasa por
 * `AnularPagoAction`. Ambas acciones están gateadas por `pagos.registrar`/`pagos.anular`
 * explícitamente (a diferencia de otros RelationManagers del proyecto, que heredan el permiso del
 * recurso dueño): son permisos independientes del catálogo de `002`, distintos de `notas.editar`.
 */
class PagosRelationManager extends RelationManager
{
    protected static string $relationship = 'pagos';

    protected static ?string $title = 'Pagos';

    protected function tienePermiso(string $slug): bool
    {
        return Filament::auth()->user()?->tienePermiso($slug, session('taller_activo_id')) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('metodo_pago_id')
                ->label('Método de pago')
                ->options(fn () => MetodoPago::query()->activos()->pluck('nombre', 'id'))
                ->searchable()
                ->required(),
            TextInput::make('monto')
                ->numeric()
                ->minValue(0.01)
                ->maxValue(fn (Livewire $livewire) => (float) $livewire->getOwnerRecord()->saldo)
                ->prefix('Bs')
                ->helperText(fn (Livewire $livewire) => 'Saldo pendiente: Bs '.number_format((float) $livewire->getOwnerRecord()->saldo, 2))
                ->required(),
            TextInput::make('referencia')->maxLength(255),
            Textarea::make('observacion')->maxLength(500),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('fecha_pago')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('metodoPago.nombre')->label('Método'),
                TextColumn::make('monto')->money('BOB'),
                TextColumn::make('referencia')->placeholder('—'),
                TextColumn::make('usuarioSistema.username')->label('Registrado por')->placeholder('—'),
                TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state) => $state === 'CONFIRMADO' ? 'success' : 'danger'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data) {
                        /** @var NotaVenta $nota */
                        $nota = $this->getOwnerRecord();

                        try {
                            return app(RegistrarPagoAction::class)->execute(
                                nota: $nota,
                                metodoPagoId: (int) $data['metodo_pago_id'],
                                monto: (float) $data['monto'],
                                referencia: $data['referencia'] ?? null,
                                observacion: $data['observacion'] ?? null,
                                usuarioSistemaId: Filament::auth()->id(),
                            );
                        } catch (BusinessException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();

                            throw (new Halt)->rollBackDatabaseTransaction();
                        }
                    })
                    ->visible(fn () => $this->tienePermiso('pagos.registrar')
                        && $this->getOwnerRecord()->estado !== 'ANULADA'),
            ])
            ->recordActions([
                Action::make('anular')
                    ->label('Anular pago')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (Pago $record) => $this->tienePermiso('pagos.anular') && $record->estado === 'CONFIRMADO')
                    ->requiresConfirmation()
                    ->action(function (Pago $record) {
                        try {
                            app(AnularPagoAction::class)->execute($record, Filament::auth()->id());
                        } catch (BusinessException $exception) {
                            Notification::make()->danger()->title($exception->getMessage())->send();

                            return;
                        }

                        Notification::make()->success()->title('Pago anulado.')->send();
                    }),
            ])
            ->defaultSort('fecha_pago', 'desc');
    }
}
