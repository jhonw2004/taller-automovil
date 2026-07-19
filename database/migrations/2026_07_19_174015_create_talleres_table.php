<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talleres', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable()->default('Taller sin nombre');
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('horario')->nullable();
            $table->double('lat', 10, 7);
            $table->double('lon', 10, 7);
            $table->string('osm_id')->nullable()->unique();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE talleres ADD COLUMN geom geometry(Point, 4326)');
        DB::statement('UPDATE talleres SET geom = ST_SetSRID(ST_MakePoint(lon, lat), 4326)');
        DB::statement('CREATE INDEX talleres_geom_idx ON talleres USING GIST (geom)');
    }

    public function down(): void
    {
        Schema::dropIfExists('talleres');
    }
};
