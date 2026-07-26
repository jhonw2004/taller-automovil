<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taller_id')->nullable()->constrained('talleres');
            $table->string('nombre', 100);
            $table->string('slug', 120);
            $table->text('descripcion')->nullable();
            $table->boolean('es_sistema')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestampsTz();

            $table->unique(['taller_id', 'slug']);
        });

        // Un rol global (taller_id NULL) no puede repetir slug con otro rol global. Los roles
        // por taller ya quedan cubiertos por el UNIQUE(taller_id, slug) de arriba, pero ese
        // UNIQUE no aplica entre dos filas con taller_id NULL (Postgres trata NULL <> NULL).
        DB::statement('CREATE UNIQUE INDEX roles_slug_global_unique ON roles (slug) WHERE taller_id IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
