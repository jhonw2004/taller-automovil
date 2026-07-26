<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talleres_horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taller_id')->constrained('talleres');
            $table->smallInteger('dia_semana');
            $table->time('hora_apertura')->nullable();
            $table->time('hora_cierre')->nullable();
            $table->boolean('cerrado')->default(false);
            $table->timestampsTz();

            $table->unique(['taller_id', 'dia_semana']);
        });

        DB::statement('ALTER TABLE talleres_horarios ADD CONSTRAINT talleres_horarios_dia_semana_check CHECK (dia_semana BETWEEN 1 AND 7)');
        DB::statement('ALTER TABLE talleres_horarios ADD CONSTRAINT talleres_horarios_horas_obligatorias_check CHECK (cerrado = TRUE OR (hora_apertura IS NOT NULL AND hora_cierre IS NOT NULL))');
        DB::statement('ALTER TABLE talleres_horarios ADD CONSTRAINT talleres_horarios_cierre_after_apertura_check CHECK (hora_cierre > hora_apertura)');
    }

    public function down(): void
    {
        Schema::dropIfExists('talleres_horarios');
    }
};
