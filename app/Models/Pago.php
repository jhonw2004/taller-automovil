<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sin `BelongsToTaller`: el aislamiento por taller ya lo garantiza la nota padre vía
 * `nota_venta_id`, mismo criterio que `NotaVentaLinea` (012). Se crea/anula únicamente vía
 * `RegistrarPagoAction`/`AnularPagoAction` (`app/Actions/Pagos/`) — nunca `::create()` directo
 * desde Filament, para que `NotaVenta::recalcularTotales()` siempre quede en el mismo estado que
 * la suma real de pagos confirmados.
 */
class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'nota_venta_id',
        'metodo_pago_id',
        'usuario_sistema_id',
        'fecha_pago',
        'monto',
        'referencia',
        'observacion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_pago' => 'datetime',
            'monto' => 'decimal:2',
        ];
    }

    public function notaVenta(): BelongsTo
    {
        return $this->belongsTo(NotaVenta::class, 'nota_venta_id');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class);
    }

    public function usuarioSistema(): BelongsTo
    {
        return $this->belongsTo(UsuarioSistema::class);
    }
}
