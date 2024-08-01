<?php

namespace App\Http\Requests\Admin\Pagos\Lista;

use App\Models\Pago;
use App\Models\Parcela;
use Illuminate\Foundation\Http\FormRequest;

class RefinanciarRequest extends FormRequest
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
            'codigo' => ['required', 'exists:parcelas,codigo'],
            'cuotas' => ['required', 'integer', 'min:1', function ($attribute, $value, $fail) {
                $parcela = Parcela::where('codigo', $this->codigo)->first();
                $pago = Pago::where('idparcela', $parcela->id)->first();
                $monto_total_restante = $parcela->idmoneda == 1 ? $pago->monto_total_restante_uf : $pago->monto_total_restante_clp;
                $monto_total_restante = ($monto_total_restante < 0 ? 0 : $monto_total_restante);
                // if($pago->monto_total_restante > 0){
                if ($monto_total_restante > 0) {
                    return true;
                } else {
                    $fail('La parcela no puede ser refinanciado porque su Saldo Actual es de 0.');
                }
            }],
            'fecha' => ['required', 'date'],
        ];
    }

    public function attributes()
    {
        return [
            'cuotas' => 'nuevo nro. de cuotas',
            'fecha' => 'Fecha de pago vencimiento',
        ];
    }
}
