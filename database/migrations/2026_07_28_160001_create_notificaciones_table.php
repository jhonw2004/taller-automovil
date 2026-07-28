<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `usuario_marketplace_id`/`usuario_sistema_id` FK NULL con CHECK de exactamente uno no nulo
 * (014-plan.md) — mismo patrón de destinatario mutuamente excluyente que `servicio_catalogo_id`/
 * `repuesto_id` en `notas_venta_lineas` (012). Sin `taller_id` propio como tenant boundary: el
 * destinatario ya es el usuario, `taller_id`/`orden_trabajo_id` son solo referencias opcionales de
 * contexto para la UI (ej. link directo a la orden). Sin soft delete: una notificación leída no se
 * borra, queda como historial personal del usuario (constitution.md no la lista en el catálogo de
 * borrado lógico ni en el de nunca-borrado-físico — no hay borrado en el MVP, ver spec.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_marketplace_id')->nullable()->constrained('usuarios_marketplace')->cascadeOnDelete();
            $table->foreignId('usuario_sistema_id')->nullable()->constrained('usuarios_sistema')->cascadeOnDelete();
            $table->foreignId('taller_id')->nullable()->constrained('talleres')->nullOnDelete();
            $table->foreignId('orden_trabajo_id')->nullable()->constrained('ordenes_trabajo')->nullOnDelete();
            $table->string('tipo', 50);
            $table->string('titulo', 255);
            $table->text('mensaje');
            $table->jsonb('data')->nullable();
            $table->boolean('leida')->default(false);
            $table->timestampTz('leida_at')->nullable();
            $table->timestampsTz();

            $table->index(['usuario_marketplace_id', 'leida']);
            $table->index(['usuario_sistema_id', 'leida']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE notificaciones ADD CONSTRAINT notificaciones_destinatario_unico_check CHECK (
                (usuario_marketplace_id IS NOT NULL)::int + (usuario_sistema_id IS NOT NULL)::int = 1
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
