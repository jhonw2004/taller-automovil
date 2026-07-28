<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sin `taller_id` propio (013-plan.md no lo lista): el aislamiento por taller ya lo garantiza
     * la nota padre vía `nota_venta_id`, mismo criterio que `notas_venta_lineas` (012). Sin soft
     * delete: un pago nunca se borra físicamente (constitution.md §2), se anula cambiando `estado`
     * a `ANULADO` — por eso la tabla sí lleva `updated_at` (a diferencia de un historial append-only
     * real como `ordenes_trabajo_estados_historial`).
     */
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_venta_id')->constrained('notas_venta');
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago');
            $table->foreignId('usuario_sistema_id')->nullable()->constrained('usuarios_sistema')->nullOnDelete();
            $table->timestampTz('fecha_pago')->useCurrent();
            $table->decimal('monto', 12, 2);
            $table->string('referencia', 255)->nullable();
            $table->string('observacion', 500)->nullable();
            $table->string('estado', 20)->default('CONFIRMADO');
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE pagos ADD CONSTRAINT pagos_monto_check CHECK (monto > 0)');
        DB::statement("ALTER TABLE pagos ADD CONSTRAINT pagos_estado_check CHECK (estado IN ('CONFIRMADO','ANULADO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
