<?php

namespace App\Http\Requests;

use App\Models\ApiClient;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreLicenciaRequest extends FormRequest
{
    private const TIPOS_NO_REGISTRABLES = ['franquicia', 'tardanza', 'inasistencia'];
    public function authorize(): bool
    {
        $client = $this->user();

        return $client instanceof ApiClient && $client->is_active;
    }

    // Mismo mensaje que usa LegajoController::show_dni
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
        // El otro sistema manda nro_resolucion como número (123); lo guardamos
        // como texto por si algún día viene "123/2026".
        if (is_int($this->nro_resolucion) || is_float($this->nro_resolucion)) {
            $this->merge(['nro_resolucion' => (string) $this->nro_resolucion]);
        }

        // El acuerdo habla de fecha con hora ("2026-09-20 14:30:00") y el ejemplo
        // de fecha sola. Aceptamos ambas y nos quedamos con la fecha.
        if (is_string($this->fecha_aprobacion)
            && preg_match('/^(\d{4}-\d{2}-\d{2})[ T]\d{2}:\d{2}(:\d{2})?$/', $this->fecha_aprobacion, $m)) {
            $this->merge(['fecha_aprobacion' => $m[1]]);
        }
    }

    public function rules(): array
    {
        return [
            'id_solicitud' => ['required', 'integer', 'min:1'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'dias_solicitados' => ['required', 'integer', 'min:1', 'max:366'],
            'motivo' => ['nullable', 'string', 'max:500'],
            'nro_resolucion' => ['nullable', 'string', 'max:50'],
            'fecha_aprobacion' => ['required', 'date_format:Y-m-d'],
            'tipo_licencia' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $tipo = mb_strtolower(trim((string) $value));

                    foreach (self::TIPOS_NO_REGISTRABLES as $prefijo) {
                        if (str_starts_with($tipo, $prefijo)) {
                            $fail('Este tipo no se registra en el legajo: solo se registran licencias aprobadas.');

                            return;
                        }
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_fin.after_or_equal' => 'La fecha_fin no puede ser anterior a fecha_inicio.',
            'date_format' => 'El campo :attribute debe tener formato AAAA-MM-DD.',
        ];
    }
}
