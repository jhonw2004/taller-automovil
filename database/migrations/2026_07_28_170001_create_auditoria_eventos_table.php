<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoría de eventos de negocio (015-plan.md): creación de usuario, asignación de rol,
 * creación de taller, aprobación/rechazo de solicitud, anulación de orden/nota/pago, ajuste de
 * inventario, cambio de propietario/visibilidad, y acceso de Super Admin fuera de su taller
 * (`App\Traits\BelongsToTaller::sinScope()`). Append-only: sin `updated_at`.
 *
 * `usuario_marketplace_id`/`usuario_sistema_id` FK NULL, **no exactamente uno** (a diferencia del
 * destinatario de `notificaciones` en 014): un evento siempre tiene un único tipo de actor, pero
 * puede no tener ninguno (ej. un comando programado). CHECK que rechaza que ambos estén no-nulos
 * a la vez, sin exigir que al menos uno lo esté.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_marketplace_id')->nullable()->constrained('usuarios_marketplace')->nullOnDelete();
            $table->foreignId('usuario_sistema_id')->nullable()->constrained('usuarios_sistema')->nullOnDelete();
            $table->foreignId('taller_id')->nullable()->constrained('talleres')->nullOnDelete();
            $table->string('evento', 100);
            $table->string('entidad_tipo', 100)->nullable();
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->jsonb('datos')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['taller_id', 'created_at']);
            $table->index('evento');
            $table->index(['entidad_tipo', 'entidad_id']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE auditoria_eventos ADD CONSTRAINT auditoria_eventos_actor_unico_check CHECK (
                (usuario_marketplace_id IS NOT NULL)::int + (usuario_sistema_id IS NOT NULL)::int <= 1
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_eventos');
    }
};
