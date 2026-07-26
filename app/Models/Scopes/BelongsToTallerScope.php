<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BelongsToTallerScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tallerId = session('taller_activo_id');

        if ($tallerId) {
            $builder->where($model->getTable().'.taller_id', $tallerId);
        }
    }
}
