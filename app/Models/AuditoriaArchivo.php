<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class AuditoriaArchivo extends Model
{

    protected $connection = 'historico';

    protected $table = 'auditoria_archivos';

    const UPDATED_AT = null;

    protected $guarded = [];
}
