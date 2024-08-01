<?php

namespace App\Http\Requests\Admin\Proyectos;

use App\Models\Parcela;
use Illuminate\Foundation\Http\FormRequest;

class ParcelaupdateRequest extends FormRequest
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
            'id' => ['required', 'exists:parcelas,id'],
            'nombre' => ['required', 'string'],
            'precio' => ['nullable', 'numeric', function ($attribute, $value, $fail) {
                $parcela = Parcela::where('id', $this->id)->first();
                if ($parcela->idusuario == null) {
                    return true;
                } else {
                    if ($this->idmoneda == 1) {
                        if ($parcela->precio_uf == $value) {
                            return true;
                        } else if ($value != $parcela->precio_uf) {
                            $fail('El Precio de la parcela no puede ser actualizado porque ya esta asignado a un cliente');
                        }
                    } else {
                        if ($parcela->precio_clp == $value) {
                            return true;
                        } else if ($value != $parcela->precio_clp) {
                            $fail('El Precio de la parcela no puede ser actualizado porque ya esta asignado a un cliente');
                        }
                    }
                }
            }],
            'idestado' => ['required', 'exists:estados,id'],
            'idmoneda' => ['required', 'exists:monedas,id', function ($attribute, $value, $fail) {
                $parcela = Parcela::where('id', $this->id)->first();
                if ($parcela->idusuario == null) {
                    return true;
                } else {
                    if ($parcela->idmoneda == $value) {
                        return true;
                    } else if ($value != $parcela->idmoneda) {
                        $fail('El tipo de la moneda de la parcela no puede ser actualizado porque ya esta asignado a un cliente');
                    }
                }
            }]
        ];
    }

    public function attributes()
    {
        return [
            'id' => 'parcela',
            'idestado' => 'estado',
            'idmoneda' => 'monedas',
        ];
    }
}
