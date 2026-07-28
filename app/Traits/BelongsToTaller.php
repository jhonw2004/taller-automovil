<?php

namespace App\Traits;

use App\Actions\Auditoria\RegistrarEventoAuditoriaAction;
use App\Models\Scopes\BelongsToTallerScope;
use App\Models\Taller;
use Closure;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToTaller
{
    protected static function bootBelongsToTaller(): void
    {
        static::addGlobalScope(new BelongsToTallerScope);

        static::creating(function ($model) {
            if (empty($model->taller_id)) {
                $model->taller_id = session('taller_activo_id');
            }
        });
    }

    public function taller(): BelongsTo
    {
        return $this->belongsTo(Taller::class);
    }

    /**
     * `Auth::guard('sistema')` explícito, no `auth()->user()` (guard por defecto = `web`,
     * `config/auth.php`): este método solo lo invoca Super Admin desde el panel `/erp`/`/admin`
     * (guard `sistema`) — con el guard por defecto el causante quedaba siempre `null` o, peor,
     * el usuario marketplace que casualmente tuviera sesión abierta en la misma request. Bug real
     * detectado al migrar este log a `auditoria_eventos` en 015, nunca ejercitado antes porque
     * `sinScope()` no tiene consumidor real todavía (mismo patrón "escritor futuro" que otras
     * piezas de infraestructura preparadas sin feature que las use aún).
     */
    public static function sinScope(Closure $callback): mixed
    {
        $result = static::withoutGlobalScope(BelongsToTallerScope::class)
            ->when(true, fn ($q) => $callback($q));

        app(RegistrarEventoAuditoriaAction::class)->execute(
            evento: 'sin_global_scope',
            usuarioSistemaId: Auth::guard('sistema')->id(),
            tallerId: session('taller_activo_id'),
            datos: ['model' => static::class],
        );

        return $result;
    }
}
