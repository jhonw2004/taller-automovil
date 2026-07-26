<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taller_id')->constrained('talleres');
            $table->string('codigo', 50);
            $table->string('tipo_persona', 20)->default('NATURAL');
            $table->string('nombre');
            $table->string('apellido')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('nit_ci', 50)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
            $table->softDeletes();

            $table->unique(['taller_id', 'codigo']);
        });

        DB::statement("ALTER TABLE clientes ADD CONSTRAINT clientes_tipo_persona_check CHECK (tipo_persona IN ('NATURAL','JURIDICA'))");
        DB::statement('CREATE UNIQUE INDEX clientes_taller_nit_ci_unique ON clientes (taller_id, nit_ci) WHERE nit_ci IS NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
