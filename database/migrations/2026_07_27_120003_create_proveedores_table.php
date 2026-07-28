<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taller_id')->constrained('talleres');
            $table->string('nombre');
            $table->string('contacto')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->string('nit', 50)->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
            $table->softDeletes();
        });

        DB::statement('CREATE UNIQUE INDEX proveedores_taller_nit_unique ON proveedores (taller_id, nit) WHERE nit IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
