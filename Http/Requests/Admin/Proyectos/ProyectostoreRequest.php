<?php

namespace App\Http\Requests\Admin\Proyectos;

use Illuminate\Foundation\Http\FormRequest;

class ProyectostoreRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'unique:proyectos,descripcion'],
            'ubicacion' => ['required', 'string'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'numparcelas' => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes()
    {
        return [
            'imagen' => 'imagen del proyecto',
            'numparcelas' => 'n° de parcelas',
        ];
    }
}
