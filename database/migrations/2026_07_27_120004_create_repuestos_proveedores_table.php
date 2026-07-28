<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Relacion N:M repuesto<->proveedor (010-plan.md): precio de referencia por proveedor,
     * codigo propio del proveedor y marca de proveedor principal. PK compuesta, sin `id`
     * propio, mismo patron que `talleres_categorias` (003-gestion-talleres).
     */
    public function up(): void
    {
        Schema::create('repuestos_proveedores', function (Blueprint $table) {
            $table->foreignId('repuesto_id')->constrained('repuestos')->cascadeOnDelete();
            $table->foreignId('proveedor_id')->constrained('proveedores')->cascadeOnDelete();
            $table->string('codigo_proveedor', 50)->nullable();
            $table->decimal('precio_referencia', 12, 2)->nullable();
            $table->integer('tiempo_entrega_dias')->nullable();
            $table->boolean('es_principal')->default(false);
            $table->timestampsTz();

            $table->primary(['repuesto_id', 'proveedor_id']);
        });

        DB::statement('ALTER TABLE repuestos_proveedores ADD CONSTRAINT repuestos_proveedores_precio_referencia_check CHECK (precio_referencia IS NULL OR precio_referencia >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('repuestos_proveedores');
    }
};
