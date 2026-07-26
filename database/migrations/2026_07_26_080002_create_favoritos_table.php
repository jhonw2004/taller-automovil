<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solo alta/baja (006-resenas-favoritos/plan.md): sin `updated_at`, sin soft delete — un
     * favorito eliminado se borra fisicamente, no hay nada que preservar historicamente.
     */
    public function up(): void
    {
        Schema::create('favoritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_marketplace_id')->constrained('usuarios_marketplace');
            $table->foreignId('taller_id')->constrained('talleres');
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['usuario_marketplace_id', 'taller_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favoritos');
    }
};
