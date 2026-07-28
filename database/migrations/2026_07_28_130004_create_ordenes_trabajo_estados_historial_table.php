<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only (constitution.md §2): sin `updated_at` ni `deleted_at`, nunca se edita ni se
     * borra — cada cambio de estado de una orden inserta una fila nueva, nunca se modifica una
     * existente.
     */
    public function up(): void
    {
        Schema::create('ordenes_trabajo_estados_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_trabajo_id')->constrained('ordenes_trabajo')->cascadeOnDelete();
            $table->string('estado_anterior', 30)->nullable();
            $table->string('estado_nuevo', 30);
            $table->foreignId('usuario_sistema_id')->nullable()->constrained('usuarios_sistema')->nullOnDelete();
            $table->text('observacion')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_trabajo_estados_historial');
    }
};
