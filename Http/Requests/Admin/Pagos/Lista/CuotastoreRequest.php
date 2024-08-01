<?php

namespace App\Http\Requests\Admin\Pagos\Lista;

use App\Models\Pago;
use App\Models\Pagodetalle;
use Illuminate\Foundation\Http\FormRequest;

class CuotastoreRequest extends FormRequest
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
            'id' => ['required', 'exists:pagodetalles,id'],
            'idmetodopago' => ['required', 'exists:metodopagos,id'],
            'fecha' => ['required', 'date'],
            // 'monto' => [($this->idmetodopago == 1 ? 'required' : 'nullable'), 'numeric', function($attribute, $value, $fail){
            //     if($this->idmetodopago == 1){
            //         $pagodetalle = Pagodetalle::where('id', $this->id)->first();
            //         $pago = Pago::with('parcela')->where('id', $pagodetalle->idpago)->first();
            //         $monto_cuota = ($pago->parcela->idmoneda == 1 ? $pagodetalle->monto_cuota_uf : $pagodetalle->monto_cuota_clp);
            //         if($value >= $monto_cuota){
            //             return true;
            //         }else{
            //             $fail('El monto con interes no puede ser menor al monto base');
            //         }
            //     }
            //     return true;
            // }],
            'observacion' => ['nullable', 'string'],
            'numtransaccion' => [($this->idmetodopago == 1 ? 'required' : 'nullable'), 'string'],
        ];
    }

    public function attributes()
    {
        return [
            'id' => 'Cuota',
            'idmetodopago' => 'Metodo de pago',
            'fecha' => 'Fecha de pago',
            // 'monto' => 'Monto pagado',
            'numtransaccion' => 'Numero de transaccion'
        ];
    }
}
