<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resenas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_marketplace_id')->constrained('usuarios_marketplace');
            $table->foreignId('taller_id')->constrained('talleres');
            // `ordenes_trabajo` no existe todavia (011-ordenes-trabajo, spec posterior en el orden
            // del proyecto) — se agrega la columna sin FK por ahora. La FK real se agrega en
            // `2026_07_28_180001_add_orden_trabajo_foreign_to_resenas_table.php`, una vez que
            // `011-ordenes-trabajo` crea la tabla referenciada.
            $table->unsignedBigInteger('orden_trabajo_id')->nullable();
            $table->smallInteger('calificacion');
            $table->text('comentario')->nullable();
            $table->string('estado', 20)->default('PUBLICADA');
            $table->timestampsTz();
            $table->softDeletes();

            $table->unique(['usuario_marketplace_id', 'taller_id']);
        });

        DB::statement('ALTER TABLE resenas ADD CONSTRAINT resenas_calificacion_check CHECK (calificacion BETWEEN 1 AND 5)');
        DB::statement("ALTER TABLE resenas ADD CONSTRAINT resenas_estado_check CHECK (estado IN ('PUBLICADA','OCULTA','REPORTADA'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('resenas');
    }
};
