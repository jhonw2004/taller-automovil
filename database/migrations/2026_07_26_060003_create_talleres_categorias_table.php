<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talleres_categorias', function (Blueprint $table) {
            $table->foreignId('taller_id')->constrained('talleres');
            $table->foreignId('categoria_id')->constrained('categorias');
            $table->smallInteger('orden')->default(0);
            $table->timestampsTz();

            $table->primary(['taller_id', 'categoria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talleres_categorias');
    }
};
