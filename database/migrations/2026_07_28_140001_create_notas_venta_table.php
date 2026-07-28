<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `total >= 0`, `descuento <= subtotal` y `monto_pagado <= total` no aparecen listados en
     * `012-plan.md` (solo enumera `descuento >= 0`, `total = subtotal - descuento`,
     * `saldo = total - monto_pagado`, `monto_pagado >= 0`), pero sí son criterios explícitos de
     * `012-spec.md` ("descuento no negativo ni mayor al subtotal; total no negativo; monto_pagado
     * no negativo y no supera total; saldo no negativo") — mismo criterio de lectura ya aplicado en
     * `011-ordenes-trabajo` (CHECK `total >= 0` deducido del spec, no listado literalmente en su
     * plan.md). `saldo >= 0` se deja explícito también aunque se derive algebraicamente de
     * `monto_pagado <= total`, por consistencia con el resto de columnas de saldo/total del
     * proyecto que siempre lo declaran.
     */
    public function up(): void
    {
        Schema::create('notas_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taller_id')->constrained('talleres');
            $table->string('codigo', 50);
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('orden_trabajo_id')->nullable()->constrained('ordenes_trabajo')->nullOnDelete();
            $table->foreignId('usuario_sistema_id')->nullable()->constrained('usuarios_sistema')->nullOnDelete();
            $table->date('fecha_emision')->useCurrent();
            $table->string('estado', 20)->default('EMITIDA');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('monto_pagado', 12, 2)->default(0);
            $table->decimal('saldo', 12, 2)->default(0);
            $table->timestampsTz();
            $table->softDeletes();

            $table->unique(['taller_id', 'codigo']);
        });

        DB::statement("ALTER TABLE notas_venta ADD CONSTRAINT notas_venta_estado_check CHECK (estado IN ('EMITIDA','PENDIENTE','PAGADA','ANULADA'))");
        DB::statement('ALTER TABLE notas_venta ADD CONSTRAINT notas_venta_subtotal_no_negativo_check CHECK (subtotal >= 0)');
        DB::statement('ALTER TABLE notas_venta ADD CONSTRAINT notas_venta_descuento_check CHECK (descuento >= 0)');
        DB::statement('ALTER TABLE notas_venta ADD CONSTRAINT notas_venta_descuento_no_supera_subtotal_check CHECK (descuento <= subtotal)');
        DB::statement('ALTER TABLE notas_venta ADD CONSTRAINT notas_venta_total_check CHECK (total = subtotal - descuento)');
        DB::statement('ALTER TABLE notas_venta ADD CONSTRAINT notas_venta_total_no_negativo_check CHECK (total >= 0)');
        DB::statement('ALTER TABLE notas_venta ADD CONSTRAINT notas_venta_monto_pagado_check CHECK (monto_pagado >= 0)');
        DB::statement('ALTER TABLE notas_venta ADD CONSTRAINT notas_venta_monto_pagado_no_supera_total_check CHECK (monto_pagado <= total)');
        DB::statement('ALTER TABLE notas_venta ADD CONSTRAINT notas_venta_saldo_check CHECK (saldo = total - monto_pagado)');
        DB::statement('ALTER TABLE notas_venta ADD CONSTRAINT notas_venta_saldo_no_negativo_check CHECK (saldo >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_venta');
    }
};
