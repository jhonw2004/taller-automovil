<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoría de cambios en campos sensibles de taller (015-plan.md/spec.md): nombre, teléfono,
 * email, dirección, estado, visible_en_mapa, lat, lon, geom, osm_id — incluye valores antiguo y
 * nuevo. `lat_old`/`lon_old`/`geom_old` NULL en `INSERT` (no había estado previo);
 * `datos_new`/`lat_new`/`lon_new`/`geom_new` NULL en `DELETE` (soft delete: no hay estado nuevo,
 * solo se apaga el registro). `geom_old`/`geom_new` casteados con `App\Casts\GeometryCast`
 * (mismo cast que `Taller.geom`) desde el modelo, no aquí. Append-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_talleres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taller_id')->constrained('talleres');
            $table->foreignId('usuario_sistema_id')->nullable()->constrained('usuarios_sistema')->nullOnDelete();
            $table->string('operacion', 10);
            $table->jsonb('datos_old')->nullable();
            $table->jsonb('datos_new')->nullable();
            $table->double('lat_old')->nullable();
            $table->double('lat_new')->nullable();
            $table->double('lon_old')->nullable();
            $table->double('lon_new')->nullable();
            $table->geometry('geom_old', subtype: 'point', srid: 4326)->nullable();
            $table->geometry('geom_new', subtype: 'point', srid: 4326)->nullable();
            $table->ipAddress('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['taller_id', 'created_at']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE auditoria_talleres ADD CONSTRAINT auditoria_talleres_operacion_check CHECK (
                operacion IN ('INSERT', 'UPDATE', 'DELETE')
            )
        SQL);

        DB::statement('CREATE INDEX auditoria_talleres_geom_old_idx ON auditoria_talleres USING GIST (geom_old)');
        DB::statement('CREATE INDEX auditoria_talleres_geom_new_idx ON auditoria_talleres USING GIST (geom_new)');
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_talleres');
    }
};
