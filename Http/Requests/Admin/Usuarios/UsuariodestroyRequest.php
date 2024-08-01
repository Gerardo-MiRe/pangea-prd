<?php

namespace App\Http\Requests\Admin\Usuarios;

use App\Models\Pago;
use App\Models\Proyecto;
use Illuminate\Foundation\Http\FormRequest;

class UsuariodestroyRequest extends FormRequest
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
            'id' => ['required', 'exists:users,id', function ($attribute, $value, $fail) {
                $proyecto = Proyecto::where('idusuario', $value)->first();
                $pago = Pago::where('idcliente', $value)->first();
                if (!empty($proyecto)) {
                    $fail('El usuario no puede ser eliminado porque tiene registrado uno o mas proyectos');
                } else if (!empty($pago)) {
                    $fail('El usuario no puede ser eliminado porque tiene un pago asignado');
                } else {
                    return true;
                }
            }],
        ];
    }

    public function attributes()
    {
        return [
            'id' => 'usuario',
        ];
    }
}
