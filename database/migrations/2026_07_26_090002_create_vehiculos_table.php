<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taller_id')->constrained('talleres');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->string('placa', 10);
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->smallInteger('anio')->nullable();
            $table->string('color')->nullable();
            $table->string('vin', 50)->nullable();
            $table->string('tipo_vehiculo', 30)->default('AUTO');
            $table->integer('kilometraje')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
            $table->softDeletes();

            $table->unique(['taller_id', 'placa']);
        });

        DB::statement("ALTER TABLE vehiculos ADD CONSTRAINT vehiculos_placa_check CHECK (placa ~ '^[A-Z]{3}[0-9]{3}$')");
        DB::statement('ALTER TABLE vehiculos ADD CONSTRAINT vehiculos_anio_check CHECK (anio IS NULL OR anio BETWEEN 1900 AND EXTRACT(YEAR FROM CURRENT_DATE) + 1)');
        DB::statement("ALTER TABLE vehiculos ADD CONSTRAINT vehiculos_tipo_vehiculo_check CHECK (tipo_vehiculo IN ('AUTO','MOTO','CAMIONETA','CAMION','OTRO'))");
        DB::statement('ALTER TABLE vehiculos ADD CONSTRAINT vehiculos_kilometraje_check CHECK (kilometraje >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
    }
};
