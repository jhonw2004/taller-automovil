<?php

namespace App\Http\Requests\Repuestos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas de `010-inventario-repuestos/plan.md`: `codigo`/`codigo_barras` unicos por taller activo
 * de la sesion, `stock_actual` deliberadamente fuera de las reglas (nunca se edita a mano, se
 * deriva de `inventario_movimientos` via `RegistrarMovimientoInventarioAction`). Sin
 * controlador/ruta propia todavia, mismo patron que `ClienteRequest`/`ServicioCatalogoRequest`.
 */
class RepuestoRequest extends FormRequest
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
                Rule::unique('repuestos', 'codigo')
                    ->where(fn ($query) => $query->where('taller_id', $tallerId))
                    ->ignore($this->route('repuesto')),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'codigo_barras' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('repuestos', 'codigo_barras')
                    ->where(fn ($query) => $query->where('taller_id', $tallerId))
                    ->ignore($this->route('repuesto')),
            ],
            'unidad_medida_id' => ['nullable', Rule::exists('unidades_medida', 'id')],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'precio_costo' => ['required', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'activo' => ['boolean'],
        ];
    }
}
