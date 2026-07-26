<?php

namespace App\Http\Requests\Sistema;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CambiarPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('sistema') !== null;
    }

    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'confirmed',
                Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
        ];
    }
}
