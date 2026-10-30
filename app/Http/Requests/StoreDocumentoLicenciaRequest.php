<?php

namespace App\Http\Requests;

use App\Models\Token;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreDocumentoLicenciaRequest extends FormRequest
{
    // Límite acordado con el sistema de licencias: PDF de hasta 10 MB (10240 KB)
    public const MAX_KB = 10240;

    public function authorize(): bool
    {
        $client = $this->user();

        return $client instanceof Token && $client->is_active;
    }

    // Mismo mensaje que usan los otros endpoints de la API
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            response()->json(['error' => 'Acceso no autorizado o cliente inactivo.'], 403)
        );
    }

    // Siempre JSON, aunque el cliente no envíe "Accept: application/json"
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Los datos enviados no son válidos.',
            'errors' => $validator->errors(),
        ], 422));
    }

    protected function prepareForValidation(): void
    {
        // Se acepta el DNI con puntos o guiones y se guarda solo con dígitos
        $this->merge([
            'dni' => preg_replace('/\D/', '', (string) $this->input('dni')),
        ]);
    }

    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:pdf', 'max:' . self::MAX_KB],
            'id_solicitud' => ['required', 'integer', 'min:1'],
            'dni' => ['required', 'string', 'regex:/^\d{7,8}$/'],
            'nro_resolucion' => ['nullable', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ];
    }
}
