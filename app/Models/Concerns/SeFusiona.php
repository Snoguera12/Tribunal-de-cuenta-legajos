<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Un registro de catálogo (título, institución, idioma) que se fusiona en otro NO se borra:
 * queda marcado con el registro en el que se fusionó (fusionado_en_id) y deja de listarse.
 * Se le agrega " [fusionado #id]" al nombre para que el nombre original (que es único) se pueda volver a usar.
 */
trait SeFusiona
{
    public function marcarFusionado(Model $destino): void
    {
        $sufijo = " [fusionado #{$this->getKey()}]";
        $largo = static::LARGO_NOMBRE - mb_strlen($sufijo);

        $this->forceFill([
            'nombre' => mb_substr((string) $this->nombre, 0, $largo) . $sufijo,
            'fusionado_en_id' => $destino->getKey(),
            'fusionado_en' => now(),
        ])->save();
    }

    public function estaFusionado(): bool
    {
        return $this->fusionado_en_id !== null;
    }
}
