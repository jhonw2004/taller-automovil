<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoría de accesos (015-plan.md/spec.md): login exitoso, login fallido, logout, cambio de
 * contraseña, acceso denegado. Nunca guarda `password_hash` (constitution.md §7) — solo
 * `identificador` (username/email intentado). Append-only.
 *
 * Los 5 eventos semánticos del spec se cubren combinando `tipo_acceso` (4 categorías) x
 * `resultado` (3 posibles): LOGIN+EXITOSO = login exitoso, LOGIN+DENEGADO = acceso denegado
 * (usuario autenticado pero sin `canAccessPanel()`, ej. sin rol vigente en el taller),
 * FAILED_LOGIN+FALLIDO = login fallido (credenciales incorrectas o cuenta bloqueada),
 * LOGOUT+EXITOSO = logout, PASSWORD_CHANGE+EXITOSO = cambio de contraseña.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_accesos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_marketplace_id')->nullable()->constrained('usuarios_marketplace')->nullOnDelete();
            $table->foreignId('usuario_sistema_id')->nullable()->constrained('usuarios_sistema')->nullOnDelete();
            $table->foreignId('taller_id')->nullable()->constrained('talleres')->nullOnDelete();
            $table->string('tipo_acceso', 30);
            $table->string('resultado', 20);
            $table->string('identificador', 255)->nullable();
            $table->ipAddress('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['usuario_sistema_id', 'created_at']);
            $table->index(['usuario_marketplace_id', 'created_at']);
            $table->index('tipo_acceso');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE auditoria_accesos ADD CONSTRAINT auditoria_accesos_actor_unico_check CHECK (
                (usuario_marketplace_id IS NOT NULL)::int + (usuario_sistema_id IS NOT NULL)::int <= 1
            )
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE auditoria_accesos ADD CONSTRAINT auditoria_accesos_tipo_acceso_check CHECK (
                tipo_acceso IN ('LOGIN', 'LOGOUT', 'PASSWORD_CHANGE', 'FAILED_LOGIN')
            )
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE auditoria_accesos ADD CONSTRAINT auditoria_accesos_resultado_check CHECK (
                resultado IN ('EXITOSO', 'FALLIDO', 'DENEGADO')
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_accesos');
    }
};
