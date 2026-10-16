<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class ApiClient extends Model
{
    use RegistraAuditoria;

    use HasApiTokens;

    protected $fillable = [
        'nombre',
        'is_active',
        'is_token'
    ];
    
}
