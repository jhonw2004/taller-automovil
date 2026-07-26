<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Las tablas `users` y `password_reset_tokens` del skeleton de Laravel no se usan en esta
        // arquitectura: la identidad real vive en `identidades`/`usuarios_marketplace`/`usuarios_sistema`
        // (spec 001-identidad-autenticacion). El marketplace no tiene contraseña (solo Google OAuth) y
        // no hay recuperación de contraseña por email para usuario sistema (fuera de alcance del MVP).
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
