<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sin columna `estado` a propósito (012-plan.md no la define, a diferencia de
     * `ordenes_trabajo_servicios`/`ordenes_trabajo_repuestos`): una línea de nota no tiene máquina
     * de estados propia en el MVP, la nota completa se anula de una vez (`NotaVenta.estado`), no
     * línea por línea.
     */
    public function up(): void
    {
        Schema::create('notas_venta_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_venta_id')->constrained('notas_venta')->cascadeOnDelete();
            $table->foreignId('servicio_catalogo_id')->nullable()->constrained('servicios_catalogo');
            $table->foreignId('repuesto_id')->nullable()->constrained('repuestos');
            $table->string('descripcion', 255);
            $table->decimal('cantidad', 12, 3)->default(1);
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE notas_venta_lineas ADD CONSTRAINT notas_venta_lineas_servicio_repuesto_excluyentes_check CHECK (NOT (servicio_catalogo_id IS NOT NULL AND repuesto_id IS NOT NULL))');
        DB::statement('ALTER TABLE notas_venta_lineas ADD CONSTRAINT notas_venta_lineas_cantidad_check CHECK (cantidad > 0)');
        DB::statement('ALTER TABLE notas_venta_lineas ADD CONSTRAINT notas_venta_lineas_precio_unitario_check CHECK (precio_unitario >= 0)');
        DB::statement('ALTER TABLE notas_venta_lineas ADD CONSTRAINT notas_venta_lineas_descuento_check CHECK (descuento >= 0)');
        DB::statement('ALTER TABLE notas_venta_lineas ADD CONSTRAINT notas_venta_lineas_subtotal_check CHECK (subtotal = cantidad * precio_unitario - descuento)');
        DB::statement('ALTER TABLE notas_venta_lineas ADD CONSTRAINT notas_venta_lineas_subtotal_no_negativo_check CHECK (subtotal >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_venta_lineas');
    }
};
