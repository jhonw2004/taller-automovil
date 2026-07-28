<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `spatie/laravel-activitylog` se usó como stand-in genérico de `auditoria_eventos` en varias
 * Actions (003/004/006/008/011/012/013) mientras `015-auditoria` no existía, documentado
 * explícitamente en cada una de ellas ("revisar cuando se implemente 015"). Ahora que las tres
 * tablas reales existen con el esquema exacto de `015-plan.md` (destinatario mutuamente
 * excluyente, `taller_id`, `entidad_tipo`/`entidad_id`, sin el shape polimórfico de Spatie), el
 * paquete queda completamente sin uso — se remueve por el mismo motivo y con el mismo criterio
 * que `spatie/laravel-permission` en `002-roles-permisos`: no vale la pena mantener instalada una
 * dependencia cuyo esquema no coincide con los criterios de negocio del proyecto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('activity_log');
    }

    public function down(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('attribute_changes')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }
};
