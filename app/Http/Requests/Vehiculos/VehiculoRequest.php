<?php

namespace App\Http\Requests\Vehiculos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas de `007-clientes-vehiculos/plan.md`: `placa` única por taller (normalizada sin guion
 * antes de validar el regex, para que "ABC-123"/"abc 123" no rechacen por formato), `cliente_id`
 * debe pertenecer al taller activo (Rule::exists acotado, no solo `exists:clientes,id` a secas —
 * evita asignar un vehículo a un cliente de otro taller).
 */
class VehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('sistema') !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('placa')) {
            $this->merge([
                'placa' => strtoupper(str_replace(['-', ' '], '', $this->input('placa'))),
            ]);
        }
    }

    public function rules(): array
    {
        $tallerId = session('taller_activo_id');

        return [
            'cliente_id' => [
                'required',
                'integer',
                Rule::exists('clientes', 'id')->where(fn ($query) => $query->where('taller_id', $tallerId)),
            ],
            'placa' => [
                'required',
                'string',
                'regex:/^[A-Z]{3}[0-9]{3}$/',
                Rule::unique('vehiculos', 'placa')
                    ->where(fn ($query) => $query->where('taller_id', $tallerId))
                    ->ignore($this->route('vehiculo')),
            ],
            'marca' => ['nullable', 'string', 'max:255'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'anio' => ['nullable', 'integer', 'between:1900,'.((int) date('Y') + 1)],
            'color' => ['nullable', 'string', 'max:255'],
            'vin' => ['nullable', 'string', 'max:50'],
            'tipo_vehiculo' => ['required', Rule::in(['AUTO', 'MOTO', 'CAMIONETA', 'CAMION', 'OTRO'])],
            'kilometraje' => ['nullable', 'integer', 'min:0'],
            'activo' => ['boolean'],
        ];
    }
}
