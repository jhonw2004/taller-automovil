<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credenciales_sistema', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_sistema_id')->unique()->constrained('usuarios_sistema');
            $table->text('password_hash');
            $table->boolean('debe_cambiar_password')->default(true);
            $table->timestampTz('password_changed_at')->nullable();
            $table->smallInteger('intentos_fallidos')->default(0);
            $table->timestampTz('bloqueado_hasta')->nullable();
            $table->timestampTz('password_expires_at')->default(DB::raw("(NOW() + INTERVAL '90 days')"));
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credenciales_sistema');
    }
};
