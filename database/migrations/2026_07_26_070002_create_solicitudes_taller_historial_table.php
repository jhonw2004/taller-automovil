<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only (constitution.md §2): sin `updated_at`, nunca se edita ni se borra.
     */
    public function up(): void
    {
        Schema::create('solicitudes_taller_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_taller_id')->constrained('solicitudes_taller')->cascadeOnDelete();
            $table->string('estado_anterior', 20)->nullable();
            $table->string('estado_nuevo', 20);
            $table->foreignId('usuario_sistema_id')->nullable()->constrained('usuarios_sistema')->nullOnDelete();
            $table->text('observacion')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_taller_historial');
    }
};
