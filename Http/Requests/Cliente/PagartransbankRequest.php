<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;

class PagartransbankRequest extends FormRequest
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
            'seleccionado' => ['required', 'integer', 'min:1'],
            'lista' => ['nullable', 'array', 'min:1'],
            'moneda' => ['required', 'exists:monedas,id'],
        ];
    }

    // public function attributes()
    // {
    //     return [
    //         'seleccionado' => 'seleccionar',
    //         'lista' => 'monto'
    //     ];
    // }

    public function messages()
    {
        return [
            'seleccionado.required' => 'Debe seleccionar al menos una cuota para continuar.',
            'seleccionado.min' => 'Debe seleccionar al menos una cuota para continuar.',
        ];
    }
}
