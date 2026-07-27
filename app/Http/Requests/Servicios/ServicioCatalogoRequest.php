<?php

namespace App\Http\Requests\Servicios;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas de `009-catalogo-servicios/plan.md`: `codigo` único por taller activo de la sesión (no
 * por parámetro del request, mismo criterio que `ClienteRequest`/`VehiculoRequest` de `007`). Sin
 * controlador/ruta propia todavía — Filament consume estas mismas reglas de forma equivalente en
 * su Schema, no instanciando este Request directamente.
 */
class ServicioCatalogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('sistema') !== null;
    }

    public function rules(): array
    {
        $tallerId = session('taller_activo_id');

        return [
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('servicios_catalogo', 'codigo')
                    ->where(fn ($query) => $query->where('taller_id', $tallerId))
                    ->ignore($this->route('servicio')),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'precio_base' => ['required', 'numeric', 'min:0'],
            'duracion_minutos' => ['nullable', 'integer', 'min:1'],
            'activo' => ['boolean'],
        ];
    }
}
