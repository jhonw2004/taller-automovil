<?php

namespace App\Http\Requests\Resenas;

use Illuminate\Foundation\Http\FormRequest;

/**
 * `authorize()` en `true`: el guard `web` ya lo exige la ruta (middleware `auth:web`, spec.md);
 * aquí solo se valida la forma del payload.
 */
class GuardarResenaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'taller_id' => ['required', 'integer', 'exists:talleres,id'],
            'calificacion' => ['required', 'integer', 'between:1,5'],
            'comentario' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
