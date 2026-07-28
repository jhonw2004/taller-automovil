<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_trabajo_notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_trabajo_id')->constrained('ordenes_trabajo')->cascadeOnDelete();
            $table->foreignId('usuario_sistema_id')->nullable()->constrained('usuarios_sistema')->nullOnDelete();
            $table->string('tipo', 20)->default('INTERNA');
            $table->text('nota');
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE ordenes_trabajo_notas ADD CONSTRAINT ordenes_trabajo_notas_tipo_check CHECK (tipo IN ('INTERNA','PUBLICA'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_trabajo_notas');
    }
};
