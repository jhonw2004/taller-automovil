<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios_sistema', function (Blueprint $table) {
            $table->id();
            $table->foreignId('identidad_id')->unique()->constrained('identidades');
            $table->string('username', 50)->unique();
            $table->string('nombre', 100);
            $table->string('apellido', 100)->nullable();
            $table->timestampTz('ultimo_acceso_at')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios_sistema');
    }
};
