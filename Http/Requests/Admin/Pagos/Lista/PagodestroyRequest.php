<?php

namespace App\Http\Requests\Admin\Pagos\Lista;

use Illuminate\Foundation\Http\FormRequest;

class PagodestroyRequest extends FormRequest
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
            // Define your validation rules here
        ];
    }

    public function attributes()
    {
        return [
            'id' => 'pago',
        ];
    }
}