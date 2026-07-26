<?php

namespace App\Traits;

use App\Models\Scopes\BelongsToTallerScope;
use App\Models\Taller;
use Closure;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public static function sinScope(Closure $callback): mixed
    {
        $result = static::withoutGlobalScope(BelongsToTallerScope::class)
            ->when(true, fn ($q) => $callback($q));

        activity()
            ->event('sin_global_scope')
            ->causedBy(auth()->user())
            ->withProperties([
                'model' => static::class,
                'taller_id_accedido' => session('taller_activo_id'),
            ])
            ->log('Acceso sin filtro de taller');

        return $result;
    }
}
