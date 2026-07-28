<?php

namespace App\Http\Requests\Proveedores;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas de `010-inventario-repuestos/plan.md`: `nit` unico por taller activo de la sesion,
 * mismo patron que `RepuestoRequest`/`ClienteRequest`.
 */
class ProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('sistema') !== null;
    }

    public function rules(): array
    {
        $tallerId = session('taller_activo_id');

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'contacto' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'nit' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('proveedores', 'nit')
                    ->where(fn ($query) => $query->where('taller_id', $tallerId))
                    ->ignore($this->route('proveedor')),
            ],
            'observaciones' => ['nullable', 'string'],
            'activo' => ['boolean'],
        ];
    }
}
