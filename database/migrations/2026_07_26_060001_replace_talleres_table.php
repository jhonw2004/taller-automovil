<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El prototipo original de `talleres` (2026_07_19_174015) no coincide con el esquema de
     * 003-gestion-talleres/plan.md: falta slug/estado/visible_en_mapa/calificacion_promedio/
     * cantidad_resenas, sobra la columna `horario` (string libre, reemplazada por la tabla
     * `talleres_horarios`). No hay datos valiosos que preservar (era prototipo), así que se
     * reemplaza con DROP + CREATE en vez de una migración incremental.
     *
     * `roles.taller_id` y `asignaciones_rol.taller_id` (002-roles-permisos) ya tienen FK hacia
     * la tabla vieja — hay que soltarlas antes del DROP y volver a crearlas contra la tabla nueva,
     * o el DROP falla por violación de integridad referencial (brecha documentada en resume.md).
     */
    public function up(): void
    {
        Schema::table('asignaciones_rol', function (Blueprint $table) {
            $table->dropForeign(['taller_id']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['taller_id']);
        });

        Schema::dropIfExists('talleres');

        Schema::create('talleres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propietario_usuario_sistema_id')->nullable()->constrained('usuarios_sistema');
            $table->string('nombre', 255);
            $table->string('slug', 255)->unique();
            $table->text('descripcion')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('nit', 50)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->double('lat');
            $table->double('lon');
            $table->geometry('geom', subtype: 'point', srid: 4326);
            $table->string('osm_id', 255)->nullable()->unique();
            $table->text('logo_url')->nullable();
            $table->string('estado', 20)->default('ACTIVO');
            $table->boolean('visible_en_mapa')->default(false);
            $table->decimal('calificacion_promedio', 3, 2)->default(0);
            $table->unsignedInteger('cantidad_resenas')->default(0);
            $table->timestampsTz();
            $table->softDeletes();
        });

        DB::statement('ALTER TABLE talleres ADD CONSTRAINT talleres_lat_check CHECK (lat BETWEEN -90 AND 90)');
        DB::statement('ALTER TABLE talleres ADD CONSTRAINT talleres_lon_check CHECK (lon BETWEEN -180 AND 180)');
        DB::statement("ALTER TABLE talleres ADD CONSTRAINT talleres_estado_check CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))");

        DB::statement('CREATE INDEX talleres_geom_idx ON talleres USING GIST (geom)');
        DB::statement('CREATE INDEX talleres_estado_idx ON talleres (estado)');
        DB::statement('CREATE INDEX talleres_visible_en_mapa_idx ON talleres (visible_en_mapa)');

        Schema::table('roles', function (Blueprint $table) {
            $table->foreign('taller_id')->references('id')->on('talleres');
        });

        Schema::table('asignaciones_rol', function (Blueprint $table) {
            $table->foreign('taller_id')->references('id')->on('talleres');
        });
    }

    public function down(): void
    {
        Schema::table('asignaciones_rol', function (Blueprint $table) {
            $table->dropForeign(['taller_id']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['taller_id']);
        });

        Schema::dropIfExists('talleres');

        Schema::create('talleres', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable()->default('Taller sin nombre');
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('horario')->nullable();
            $table->double('lat', 10, 7);
            $table->double('lon', 10, 7);
            $table->string('osm_id')->nullable()->unique();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE talleres ADD COLUMN geom geometry(Point, 4326)');
        DB::statement('CREATE INDEX talleres_geom_idx ON talleres USING GIST (geom)');

        Schema::table('roles', function (Blueprint $table) {
            $table->foreign('taller_id')->references('id')->on('talleres');
        });

        Schema::table('asignaciones_rol', function (Blueprint $table) {
            $table->foreign('taller_id')->references('id')->on('talleres');
        });
    }
};
