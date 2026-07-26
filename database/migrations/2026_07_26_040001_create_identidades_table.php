<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identidades', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);
            $table->string('email', 255)->nullable()->unique();
            $table->string('telefono', 30)->nullable();
            $table->string('estado', 20)->default('ACTIVO');
            $table->timestampsTz();
            $table->softDeletes();
        });

        DB::statement("ALTER TABLE identidades ADD CONSTRAINT identidades_tipo_check CHECK (tipo IN ('MARKETPLACE','SISTEMA'))");
        DB::statement("ALTER TABLE identidades ADD CONSTRAINT identidades_estado_check CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('identidades');
    }
};
