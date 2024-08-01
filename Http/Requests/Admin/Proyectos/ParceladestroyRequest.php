<?php

namespace App\Http\Requests\Admin\Proyectos;

use App\Models\Parcela;
use Illuminate\Foundation\Http\FormRequest;

class ParceladestroyRequest extends FormRequest
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
            'id' => ['required', 'exists:parcelas,id', function ($attribute, $value, $fail) {
                $validar = Parcela::where('id', $value)->whereNotNull('idusuario')->first();
                if (empty($validar)) {
                    return true;
                } else {
                    return $fail('No puede eliminar la parcela porque esta asignada a un cliente');
                }
            }]
        ];
    }

    public function attributes()
    {
        return [
            'id' => 'parcela',
        ];
    }
}
