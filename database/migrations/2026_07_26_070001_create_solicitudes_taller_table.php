<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_taller', function (Blueprint $table) {
            $table->id();
            $table->uuid('token_publico')->unique();
            $table->string('estado', 20)->default('PENDIENTE');

            $table->string('solicitante_nombre', 255);
            $table->string('solicitante_email', 255);
            $table->string('solicitante_telefono', 30);

            $table->string('taller_nombre', 255);
            $table->string('taller_direccion', 255)->nullable();
            $table->string('referencia', 255)->nullable();

            $table->foreignId('categoria_principal_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->double('lat')->nullable();
            $table->double('lon')->nullable();
            $table->geometry('geom', subtype: 'point', srid: 4326)->nullable();
            $table->string('osm_id', 255)->nullable();

            $table->text('comentario')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->foreignId('gestionada_por_usuario_sistema_id')->nullable()->constrained('usuarios_sistema')->nullOnDelete();

            $table->timestampTz('enviada_at')->nullable();
            $table->timestampTz('revisada_at')->nullable();
            $table->timestampTz('aprobada_at')->nullable();
            $table->timestampTz('rechazada_at')->nullable();
            $table->timestampTz('completada_at')->nullable();

            $table->foreignId('taller_id')->nullable()->unique()->constrained('talleres')->nullOnDelete();

            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE solicitudes_taller ADD CONSTRAINT solicitudes_taller_estado_check CHECK (estado IN ('PENDIENTE','EN_REVISION','APROBADA','RECHAZADA','COMPLETADA','CANCELADA'))");
        DB::statement("ALTER TABLE solicitudes_taller ADD CONSTRAINT solicitudes_taller_completada_con_taller_check CHECK (estado <> 'COMPLETADA' OR taller_id IS NOT NULL)");
        DB::statement("ALTER TABLE solicitudes_taller ADD CONSTRAINT solicitudes_taller_rechazada_sin_taller_check CHECK (estado <> 'RECHAZADA' OR taller_id IS NULL)");
        DB::statement('ALTER TABLE solicitudes_taller ADD CONSTRAINT solicitudes_taller_lat_check CHECK (lat IS NULL OR lat BETWEEN -90 AND 90)');
        DB::statement('ALTER TABLE solicitudes_taller ADD CONSTRAINT solicitudes_taller_lon_check CHECK (lon IS NULL OR lon BETWEEN -180 AND 180)');

        DB::statement('CREATE INDEX solicitudes_taller_geom_idx ON solicitudes_taller USING GIST (geom)');
        DB::statement('CREATE INDEX solicitudes_taller_estado_idx ON solicitudes_taller (estado)');
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_taller');
    }
};
