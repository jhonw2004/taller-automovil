<?php

namespace App\Http\Requests\Clientes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas de `007-clientes-vehiculos/plan.md`: `codigo` y `nit_ci` únicos por taller activo de la
 * sesión (no por parámetro del request — un usuario ERP nunca elige el taller, ya lo trae la
 * sesión vía `BelongsToTaller`). Sin controlador/ruta propia todavía (Filament consume estas
 * mismas reglas de forma equivalente en su Schema, no instanciando este Request directamente,
 * igual que `GuardarHorarioTallerRequest` de `003`) — se testea instanciando la clase directo.
 */
class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('sistema') !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nit_ci' => $this->filled('nit_ci') ? $this->input('nit_ci') : null,
        ]);
    }

    public function rules(): array
    {
        $tallerId = session('taller_activo_id');

        return [
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('clientes', 'codigo')
                    ->where(fn ($query) => $query->where('taller_id', $tallerId))
                    ->ignore($this->route('cliente')),
            ],
            'tipo_persona' => ['required', Rule::in(['NATURAL', 'JURIDICA'])],
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['nullable', 'string', 'max:255'],
            'razon_social' => ['nullable', 'string', 'max:255'],
            'nit_ci' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('clientes', 'nit_ci')
                    ->where(fn ($query) => $query->where('taller_id', $tallerId))
                    ->ignore($this->route('cliente')),
            ],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
            'activo' => ['boolean'],
        ];
    }
}
