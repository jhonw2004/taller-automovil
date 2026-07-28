<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_trabajo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taller_id')->constrained('talleres');
            $table->string('codigo', 50);
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('vehiculo_id')->constrained('vehiculos');
            $table->foreignId('empleado_asignado_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->foreignId('creado_por_usuario_sistema_id')->nullable()->constrained('usuarios_sistema')->nullOnDelete();
            $table->string('estado', 30)->default('PENDIENTE');
            $table->string('prioridad', 10)->default('MEDIA');
            $table->timestampTz('fecha_recepcion')->useCurrent();
            $table->date('fecha_estimada_entrega')->nullable();
            $table->timestampTz('fecha_entrega_real')->nullable();
            $table->integer('kilometraje_ingreso')->nullable();
            $table->text('sintomas')->nullable();
            $table->text('diagnostico')->nullable();
            $table->text('observaciones')->nullable();
            $table->decimal('subtotal_servicios', 12, 2)->default(0);
            $table->decimal('subtotal_repuestos', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestampsTz();
            $table->softDeletes();

            $table->unique(['taller_id', 'codigo']);
        });

        DB::statement("ALTER TABLE ordenes_trabajo ADD CONSTRAINT ordenes_trabajo_estado_check CHECK (estado IN ('PENDIENTE','EN_DIAGNOSTICO','ESPERANDO_APROBACION','EN_PROGRESO','PAUSADA','COMPLETADA','ENTREGADA','ANULADA'))");
        DB::statement("ALTER TABLE ordenes_trabajo ADD CONSTRAINT ordenes_trabajo_prioridad_check CHECK (prioridad IN ('BAJA','MEDIA','ALTA'))");
        DB::statement('ALTER TABLE ordenes_trabajo ADD CONSTRAINT ordenes_trabajo_descuento_check CHECK (descuento >= 0)');
        DB::statement('ALTER TABLE ordenes_trabajo ADD CONSTRAINT ordenes_trabajo_subtotales_check CHECK (subtotal_servicios >= 0 AND subtotal_repuestos >= 0)');
        DB::statement('ALTER TABLE ordenes_trabajo ADD CONSTRAINT ordenes_trabajo_total_check CHECK (total = subtotal_servicios + subtotal_repuestos - descuento)');
        DB::statement('ALTER TABLE ordenes_trabajo ADD CONSTRAINT ordenes_trabajo_total_no_negativo_check CHECK (total >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_trabajo');
    }
};
