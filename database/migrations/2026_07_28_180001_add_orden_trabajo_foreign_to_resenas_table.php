<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `resenas.orden_trabajo_id` se creó sin FK en `006-resenas-favoritos` porque
     * `ordenes_trabajo` no existía todavía (ver comentario en
     * `2026_07_26_080001_create_resenas_table.php`). Ahora que `011-ordenes-trabajo` crea esa
     * tabla, se agrega la FK real — mismo patrón ya aplicado en
     * `2026_07_28_130006_add_orden_trabajo_repuesto_foreign_to_inventario_movimientos_table.php`.
     */
    public function up(): void
    {
        Schema::table('resenas', function (Blueprint $table) {
            $table->foreign('orden_trabajo_id')
                ->references('id')->on('ordenes_trabajo')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('resenas', function (Blueprint $table) {
            $table->dropForeign(['orden_trabajo_id']);
        });
    }
};
