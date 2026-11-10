<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

// los registros de catálogo que se fusionaron en otro quedan guardados, pero no se listan ni se pueden elegir
class SoloVigentes implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNull($model->qualifyColumn('fusionado_en_id'));
    }
}
