<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios_catalogo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taller_id')->constrained('talleres');
            $table->string('codigo', 50);
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('precio_base', 12, 2)->default(0);
            $table->integer('duracion_minutos')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
            $table->softDeletes();

            $table->unique(['taller_id', 'codigo']);
        });

        DB::statement('ALTER TABLE servicios_catalogo ADD CONSTRAINT servicios_catalogo_precio_base_check CHECK (precio_base >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios_catalogo');
    }
};
