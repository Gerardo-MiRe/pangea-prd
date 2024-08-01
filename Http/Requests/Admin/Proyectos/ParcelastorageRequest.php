<?php

namespace App\Http\Requests\Admin\Proyectos;

use Illuminate\Foundation\Http\FormRequest;

class ParcelastorageRequest extends FormRequest
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
            'nombre' => ['required', 'string'],
            'precio' => ['nullable', 'numeric'],
            'idestado' => ['required', 'exists:estados,id'],
            'idmoneda' => ['required', 'exists:monedas,id']
        ];
    }

    public function attributes()
    {
        return [
            'idestado' => 'estado',
            'idmoneda' => 'monedas',
        ];
    }
}
