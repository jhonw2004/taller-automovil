<?php

namespace App\Http\Requests\Solicitudes;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Formulario público (sin cuenta) de alta de taller (004-solicitud-alta-taller/spec.md).
 * Mínimos obligatorios: datos del solicitante + nombre del taller. Ubicación (mapa) y categoría
 * son opcionales en esta etapa — el super admin puede completarlas al aprobar (plan.md: "modal
 * pre-cargado con datos + mapa editable").
 */
class CrearSolicitudTallerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'solicitante_nombre' => ['required', 'string', 'max:255'],
            'solicitante_email' => ['required', 'email', 'max:255'],
            'solicitante_telefono' => ['required', 'string', 'max:30'],
            'taller_nombre' => ['required', 'string', 'max:255'],
            'taller_direccion' => ['nullable', 'string', 'max:255'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'categoria_principal_id' => ['nullable', 'integer', 'exists:categorias,id'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lon' => ['nullable', 'numeric', 'between:-180,180'],
            'osm_id' => ['nullable', 'string', 'max:255'],
            'comentario' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
