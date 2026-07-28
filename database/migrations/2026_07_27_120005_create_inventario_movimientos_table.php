<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only (constitution.md §2): sin `updated_at`, nunca se edita ni se borra.
     * `orden_trabajo_repuesto_id` sin FK todavia: `ordenes_trabajo_repuestos` no existe hasta
     * `011-ordenes-trabajo` (010-plan.md lo documenta explicitamente). Se agrega la FK real
     * cuando esa tabla exista.
     */
    public function up(): void
    {
        Schema::create('inventario_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taller_id')->constrained('talleres');
            $table->foreignId('repuesto_id')->constrained('repuestos');
            $table->string('tipo_movimiento', 20);
            $table->decimal('cantidad', 12, 3);
            $table->decimal('stock_anterior', 12, 3);
            $table->decimal('stock_resultante', 12, 3);
            $table->decimal('costo_unitario', 12, 2)->nullable();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->unsignedBigInteger('orden_trabajo_repuesto_id')->nullable();
            $table->foreignId('usuario_sistema_id')->nullable()->constrained('usuarios_sistema')->nullOnDelete();
            $table->string('motivo')->nullable();
            $table->string('referencia')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement("ALTER TABLE inventario_movimientos ADD CONSTRAINT inventario_movimientos_tipo_movimiento_check CHECK (tipo_movimiento IN ('ENTRADA','SALIDA','AJUSTE_POSITIVO','AJUSTE_NEGATIVO'))");
        DB::statement('ALTER TABLE inventario_movimientos ADD CONSTRAINT inventario_movimientos_cantidad_check CHECK (cantidad > 0)');
        DB::statement('ALTER TABLE inventario_movimientos ADD CONSTRAINT inventario_movimientos_stock_resultante_check CHECK (stock_resultante >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_movimientos');
    }
};
