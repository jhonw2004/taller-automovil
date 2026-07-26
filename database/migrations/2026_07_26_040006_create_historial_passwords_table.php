<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_passwords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_sistema_id')->constrained('usuarios_sistema');
            $table->text('password_hash');
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_passwords');
    }
};
