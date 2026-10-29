<?php

namespace App\Http\Requests;

use App\Models\ApiClient;
use App\Rules\ArchivoPdfOJpeg;
use Illuminate\Foundation\Http\FormRequest;

// PDF de una licencia aprobada que manda el Sistema de Licencias
class StoreDocumentoLicenciaRequest extends FormRequest
{
    // solo un cliente de la API activo (igual que el resto de los endpoints)
    public function authorize(): bool
    {
        $client = $this->user();

        return $client instanceof ApiClient && $client->is_active;
    }

    public function rules(): array
    {
        return [
            // el PDF (maximo 10 MB, igual que en el sistema) y que de verdad sea un PDF
            'archivo' => ['required', 'file', 'max:10240', 'mimes:pdf', new ArchivoPdfOJpeg()],
            // numero con el que el otro sistema conoce la licencia: evita cargar dos veces la misma
            'id_solicitud' => ['nullable', 'string', 'max:60'],
            'nro_resolucion' => ['nullable', 'string', 'max:60'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            // DNI de la persona de la licencia: no asigna solo, RRHH lo ve como sugerencia al asignar
            'dni' => ['nullable', 'digits_between:7,9'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Falta el archivo PDF (campo "archivo").',
            'archivo.mimes' => 'El archivo tiene que ser un PDF.',
            'archivo.max' => 'El PDF no puede pesar más de 10 MB.',
        ];
    }
}
