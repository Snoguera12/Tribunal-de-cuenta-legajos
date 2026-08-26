<?php

namespace App\Models;

use App\Enums\TipodocEnum;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    protected $casts = [
        'tipodoc' => TipodocEnum::class,
    ];

    protected $fillable = [
        'archivo',
        'descripcion',
        'tipodoc',
        'activo',
        'legajo_id',
    ];

    public function legajo(){
        return $this->belongsTo(Legajo::class, 'legajo_id');
    }

    protected static function booted()
    {
        static::creating(function (Documento $documento) {
            $ruta = storage_path('app/private/documentos/' . $documento->archivo);

            if (file_exists($ruta)) {
                $mime = mime_content_type($ruta);

                if (! in_array($mime, ['application/pdf', 'image/jpeg'], true)) {
                    throw new \Exception('El archivo subido no es un PDF ni una imagen JPG/JPEG válida.');
                }
            }
        });
    }
}
