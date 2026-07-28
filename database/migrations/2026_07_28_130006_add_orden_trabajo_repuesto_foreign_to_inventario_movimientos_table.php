<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `inventario_movimientos.orden_trabajo_repuesto_id` se creó sin FK en
     * `010-inventario-repuestos` porque `ordenes_trabajo_repuestos` no existía todavía (ver
     * comentario en `2026_07_27_120005_create_inventario_movimientos_table.php`). Ahora que
     * `011-ordenes-trabajo` crea esa tabla, se agrega la FK real.
     */
    public function up(): void
    {
        Schema::table('inventario_movimientos', function (Blueprint $table) {
            $table->foreign('orden_trabajo_repuesto_id')
                ->references('id')->on('ordenes_trabajo_repuestos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventario_movimientos', function (Blueprint $table) {
            $table->dropForeign(['orden_trabajo_repuesto_id']);
        });
    }
};
