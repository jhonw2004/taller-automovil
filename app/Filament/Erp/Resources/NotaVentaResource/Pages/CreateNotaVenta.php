<?php

namespace App\Filament\Erp\Resources\NotaVentaResource\Pages;

use App\Actions\Notas\CrearNotaVentaDesdeOrdenAction;
use App\Actions\Notas\CrearNotaVentaDirectaAction;
use App\Exceptions\BusinessException;
use App\Filament\Erp\Resources\NotaVentaResource;
use App\Models\OrdenTrabajo;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

/**
 * No hace `NotaVenta::create()` directo. El campo `origen` (no es una columna del modelo) decide
 * cuál de las dos Actions de `012-plan.md` se invoca: `CrearNotaVentaDesdeOrdenAction` (copia
 * líneas de una orden completada/entregada, sin tocar inventario) o `CrearNotaVentaDirectaAction`
 * (líneas manuales vía el `Repeater`, con salida de inventario real para las de tipo repuesto).
 */
class CreateNotaVenta extends CreateRecord
{
    protected static string $resource = NotaVentaResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            if (($data['origen'] ?? 'directa') === 'orden') {
                $orden = OrdenTrabajo::findOrFail($data['orden_trabajo_id']);

                return app(CrearNotaVentaDesdeOrdenAction::class)->execute($orden, Filament::auth()->id());
            }

            $lineas = collect($data['lineas'] ?? [])
                ->map(fn (array $linea) => [
                    'servicio_catalogo_id' => $linea['tipo'] === 'servicio' ? ($linea['servicio_catalogo_id'] ?? null) : null,
                    'repuesto_id' => $linea['tipo'] === 'repuesto' ? ($linea['repuesto_id'] ?? null) : null,
                    'descripcion' => $linea['descripcion'] ?? null,
                    'cantidad' => $linea['cantidad'] ?? 1,
                    'precio_unitario' => $linea['tipo'] === 'personalizada' ? ($linea['precio_unitario'] ?? null) : null,
                    'descuento' => $linea['descuento'] ?? 0,
                ])
                ->all();

            return app(CrearNotaVentaDirectaAction::class)->execute(
                clienteId: $data['cliente_id'] ?? null,
                usuarioSistemaId: Filament::auth()->id(),
                lineas: $lineas,
            );
        } catch (BusinessException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            throw (new Halt)->rollBackDatabaseTransaction();
        }
    }
}
