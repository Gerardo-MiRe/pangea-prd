<?php

namespace App\Http\Requests\Admin\Usuarios;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UsuariostoreRequest extends FormRequest
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
            'nombres' => ['required', 'string'],
            'apellidos' => ['required', 'string'],
            'email' => ['required', 'email'],
            'rut' => [function ($attribute, $value, $fail) {
                //https://gist.github.com/hernanbozan/c2564d8f0d77778b47ee828e80cb9500

                // Verifica que no esté vacio y que el string sea de tamaño mayor a 3 carácteres(1-9)
                if ((empty($value)) || strlen($value) < 3) {
                    return $fail('RUT vacío o con menos de 3 caracteres.');
                }

                // Quitar los últimos 2 valores (el guión y el dígito verificador) y luego verificar que sólo sea
                // numérico
                $parteNumerica = str_replace(substr($value, -2, 2), '', $value);

                if (!preg_match("/^[0-9]*$/", $parteNumerica)) {
                    return $fail('La parte numérica del RUT sólo debe contener números.');
                }

                $guionYVerificador = substr($value, -2, 2);
                // Verifica que el guion y dígito verificador tengan un largo de 2.
                if (strlen($guionYVerificador) != 2) {
                    return $fail('Error en el largo del dígito verificador.');
                }

                // obliga a que el dígito verificador tenga la forma -[0-9] o -[kK]
                if (!preg_match('/(^[-]{1}+[0-9kK]).{0}$/', $guionYVerificador)) {
                    return $fail('El dígito verificador no cuenta con el patrón requerido');
                }

                // Valida que sólo sean números, excepto el último dígito que pueda ser k
                if (!preg_match("/^[0-9.]+[-]?+[0-9kK]{1}/", $value)) {
                    return $fail('Error al digitar el RUT');
                }

                $rutV = preg_replace('/[\.\-]/i', '', $value);
                $dv = substr($rutV, -1);
                $numero = substr($rutV, 0, strlen($rutV) - 1);
                $i = 2;
                $suma = 0;
                foreach (array_reverse(str_split($numero)) as $v) {
                    if ($i == 8) {
                        $i = 2;
                    }
                    $suma += $v * $i;
                    ++$i;
                }
                $dvr = 11 - ($suma % 11);
                if ($dvr == 11) {
                    $dvr = 0;
                }
                if ($dvr == 10) {
                    $dvr = 'K';
                }
                if ($dvr == strtoupper($dv)) {
                    // return array('error' => false, 'msj' => 'RUT ingresado correctamente.');
                    $rut = str_replace('-', '', $value);
                    $validar = User::where('rut', $rut)->first();
                    if (empty($validar)) {
                        return true;
                    } else {
                        return $fail('El RUT ingresado le pertenece a otro usuario');
                    }
                } else {
                    return $fail('El RUT ingresado no es válido.');
                }
            }],
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
