<?php

namespace App\Http\Requests\Talleres;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarHorarioTallerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('sistema') !== null;
    }

    /**
     * Refleja los CHECK de `talleres_horarios` (003-gestion-talleres/plan.md) como reglas de
     * Form Request para dar un mensaje de error legible antes de llegar a la BD: `cerrado=false`
     * exige apertura/cierre, `cierre > apertura`, único por `(taller_id, dia_semana)`.
     */
    public function rules(): array
    {
        $cerrado = $this->boolean('cerrado');

        return [
            'taller_id' => ['required', 'integer', 'exists:talleres,id'],
            'dia_semana' => [
                'required',
                'integer',
                'between:1,7',
                Rule::unique('talleres_horarios')
                    ->where(fn ($query) => $query->where('taller_id', $this->input('taller_id')))
                    ->ignore($this->route('horario')),
            ],
            'cerrado' => ['boolean'],
            'hora_apertura' => [
                Rule::requiredIf(! $cerrado),
                'nullable',
                'date_format:H:i',
            ],
            'hora_cierre' => [
                Rule::requiredIf(! $cerrado),
                'nullable',
                'date_format:H:i',
                'after:hora_apertura',
            ],
        ];
    }
}
