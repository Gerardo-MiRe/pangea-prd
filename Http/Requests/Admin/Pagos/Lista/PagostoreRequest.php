<?php

namespace App\Http\Requests\Admin\Pagos\Lista;

use Illuminate\Foundation\Http\FormRequest;

class PagostoreRequest extends FormRequest
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
            'idproyecto' => ['required', 'exists:proyectos,id'],
            'idparcela' => ['required', 'exists:parcelas,id'],
            'idusuario' => ['required', 'exists:users,id'],
            'cuotas' => ['required', 'integer', 'min:1'],
            'monto_inicial1' => ['required', 'numeric', 'min:1'],
            'monto_inicial2' => ['required', 'numeric', 'min:1'],
            'fecha' => ['required', 'date'],
        ];
    }

    public function attributes()
    {
        return [
            'idproyecto' => 'proyecto',
            'idparcela' => 'parcela',
            'idusuario' => 'usuario',
            'monto_inicial1' => 'monto inicial %',
            'monto_inicial2' => 'monto inicial',
            'fecha' => 'Fecha de pago vencimiento',
        ];
    }
}
