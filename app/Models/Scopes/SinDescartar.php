<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

// los registros repetidos que se descartaron al fusionar o asignar quedan guardados, pero no se muestran
class SinDescartar implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNull($model->qualifyColumn('descartado_en'));
    }
}
