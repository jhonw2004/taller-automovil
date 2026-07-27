<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taller_id')->constrained('talleres');
            $table->foreignId('usuario_sistema_id')->nullable()->constrained('usuarios_sistema');
            $table->string('codigo', 50);
            $table->string('nombre');
            $table->string('apellido')->nullable();
            $table->string('cargo')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
            $table->softDeletes();

            $table->unique(['taller_id', 'codigo']);
            $table->unique(['taller_id', 'usuario_sistema_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
