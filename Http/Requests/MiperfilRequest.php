<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MiperfilRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'nombres' => ['required', 'string'],
            'apellidos' => ['required', 'string'],
            'password' => ['nullable', 'string'],
        ];
    }

    public function attributes()
    {
        return [
            'password' => 'contraseña',
        ];
    }
}
