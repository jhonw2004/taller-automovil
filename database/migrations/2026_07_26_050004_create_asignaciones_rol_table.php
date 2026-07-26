<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones_rol', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_sistema_id')->constrained('usuarios_sistema');
            $table->foreignId('rol_id')->constrained('roles');
            $table->foreignId('taller_id')->nullable()->constrained('talleres');
            $table->boolean('activo')->default(true);
            $table->foreignId('asignado_por_usuario_sistema_id')->nullable()->constrained('usuarios_sistema');
            $table->date('vigente_desde')->default(new Expression('CURRENT_DATE'));
            $table->date('vigente_hasta')->nullable();
            $table->timestampsTz();

            $table->unique(['usuario_sistema_id', 'rol_id', 'taller_id']);
        });

        // Igual que en `roles`: Postgres no considera NULL = NULL, así que el UNIQUE de arriba
        // no evita asignaciones globales duplicadas (taller_id IS NULL). Se cubre aparte.
        DB::statement('CREATE UNIQUE INDEX asignaciones_rol_global_unique ON asignaciones_rol (usuario_sistema_id, rol_id) WHERE taller_id IS NULL');

        DB::statement('ALTER TABLE asignaciones_rol ADD CONSTRAINT asignaciones_rol_vigencia_check CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)');
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_rol');
    }
};
