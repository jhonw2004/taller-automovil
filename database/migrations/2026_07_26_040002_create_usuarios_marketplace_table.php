<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios_marketplace', function (Blueprint $table) {
            $table->id();
            $table->foreignId('identidad_id')->unique()->constrained('identidades');
            $table->string('nombre', 255);
            $table->text('avatar_url')->nullable();
            $table->string('locale', 10)->nullable();
            $table->timestampTz('email_verified_at')->nullable();
            $table->timestampTz('ultimo_acceso_at')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios_marketplace');
    }
};
