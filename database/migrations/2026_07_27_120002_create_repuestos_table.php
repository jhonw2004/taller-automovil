<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taller_id')->constrained('talleres');
            $table->string('codigo', 50);
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('codigo_barras', 100)->nullable();
            $table->foreignId('unidad_medida_id')->nullable()->constrained('unidades_medida')->nullOnDelete();
            $table->decimal('stock_actual', 12, 3)->default(0);
            $table->decimal('stock_minimo', 12, 3)->default(0);
            $table->decimal('precio_costo', 12, 2)->default(0);
            $table->decimal('precio_venta', 12, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
            $table->softDeletes();

            $table->unique(['taller_id', 'codigo']);
        });

        DB::statement('ALTER TABLE repuestos ADD CONSTRAINT repuestos_stock_actual_check CHECK (stock_actual >= 0)');
        DB::statement('ALTER TABLE repuestos ADD CONSTRAINT repuestos_precio_costo_check CHECK (precio_costo >= 0)');
        DB::statement('ALTER TABLE repuestos ADD CONSTRAINT repuestos_precio_venta_check CHECK (precio_venta >= 0)');
        DB::statement('CREATE UNIQUE INDEX repuestos_taller_codigo_barras_unique ON repuestos (taller_id, codigo_barras) WHERE codigo_barras IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('repuestos');
    }
};
