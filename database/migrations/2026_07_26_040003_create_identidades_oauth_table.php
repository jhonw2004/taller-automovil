<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identidades_oauth', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_marketplace_id')->constrained('usuarios_marketplace');
            $table->string('provider', 30);
            $table->string('provider_subject', 255);
            $table->string('email', 255)->nullable();
            $table->string('nombre', 255)->nullable();
            $table->text('avatar_url')->nullable();
            $table->timestampTz('email_verified_at')->nullable();
            $table->timestampsTz();

            $table->unique(['provider', 'provider_subject']);
            $table->unique(['usuario_marketplace_id', 'provider']);
        });

        DB::statement("ALTER TABLE identidades_oauth ADD CONSTRAINT identidades_oauth_provider_check CHECK (provider IN ('GOOGLE'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('identidades_oauth');
    }
};
