<?php

namespace App\Http\Requests\Admin\Usuarios;

use Illuminate\Foundation\Http\FormRequest;

class UsuarioupdateRequest extends FormRequest
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
            'id' => ['required', 'exists:users,id'],
            'nombres' => ['required', 'string'],
            'apellidos' => ['required', 'string'],
            'password' => ['nullable', 'string'],
            'telefono' => ['nullable', 'numeric'],
            'direccion' => ['nullable', 'string'],
            'idcomuna' => ($this->idcomuna == 0 ? ['nullable'] : ['required', 'exists:comunas,id'])
        ];
    }

    public function attributes()
    {
        return [
            'email' => 'correo',
            'password' => 'contraseña',
            'idcomuna' => 'comuna'
        ];
    }
}
