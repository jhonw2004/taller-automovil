<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_trabajo_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_trabajo_id')->constrained('ordenes_trabajo')->cascadeOnDelete();
            $table->foreignId('servicio_catalogo_id')->constrained('servicios_catalogo');
            $table->smallInteger('cantidad')->default(1);
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->string('estado', 20)->default('PENDIENTE');
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE ordenes_trabajo_servicios ADD CONSTRAINT ordenes_trabajo_servicios_cantidad_check CHECK (cantidad > 0)');
        DB::statement('ALTER TABLE ordenes_trabajo_servicios ADD CONSTRAINT ordenes_trabajo_servicios_precio_unitario_check CHECK (precio_unitario >= 0)');
        DB::statement('ALTER TABLE ordenes_trabajo_servicios ADD CONSTRAINT ordenes_trabajo_servicios_descuento_check CHECK (descuento >= 0)');
        DB::statement('ALTER TABLE ordenes_trabajo_servicios ADD CONSTRAINT ordenes_trabajo_servicios_subtotal_check CHECK (subtotal = cantidad * precio_unitario - descuento)');
        DB::statement('ALTER TABLE ordenes_trabajo_servicios ADD CONSTRAINT ordenes_trabajo_servicios_subtotal_no_negativo_check CHECK (subtotal >= 0)');
        DB::statement("ALTER TABLE ordenes_trabajo_servicios ADD CONSTRAINT ordenes_trabajo_servicios_estado_check CHECK (estado IN ('PENDIENTE','REALIZADO','ANULADO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_trabajo_servicios');
    }
};
