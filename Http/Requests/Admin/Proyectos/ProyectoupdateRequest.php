<?php

namespace App\Http\Requests\Admin\Proyectos;

use App\Models\Parcela;
use App\Models\Proyecto;
use Illuminate\Foundation\Http\FormRequest;

class ProyectoupdateRequest extends FormRequest
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
            'id' => ['required', 'exists:proyectos,id'],
            'nombre' => ['required', 'string', 'unique:proyectos,descripcion,' . $this->id . ',id'],
            'ubicacion' => ['required', 'string'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'numparcelas' => ['required', 'integer', 'min:1', function ($attribute, $value, $fail) {
                $proyecto = Proyecto::where('id', $this->id)->first();
                $parcela = Parcela::where('idproyecto', $this->id)->whereNotNull('idusuario')->first();
                if (empty($parcela)) {
                    return true;
                } else {
                    if ($value >= $proyecto->numparcelas) {
                        return true;
                    } else {
                        $fail('No puede ingresar un numero menor de parcelas al numero actual (' . $proyecto->numparcelas . ')');
                    }
                }
            }],
        ];
    }

    public function attributes()
    {
        return [
            'id' => 'proyecto',
            'imagen' => 'imagen del proyecto',
            'numparcelas' => 'n° de parcelas',
        ];
    }
}
