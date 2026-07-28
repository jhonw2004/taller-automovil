<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalogo global (no por taller) segun 010-inventario-repuestos/plan.md.
     */
    public function up(): void
    {
        Schema::create('unidades_medida', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('simbolo', 10)->unique();
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades_medida');
    }
};
