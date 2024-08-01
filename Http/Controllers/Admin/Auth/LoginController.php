<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Models\Ufconversion;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest', ['only' => 'login']);
    }

    public function login()
    {
        $hoy = Carbon::now();
        return view('auth.login', compact('hoy'));
    }

    public function logearse(LoginRequest $request)
    {
        $nuevorut = str_replace('-', '', $request->rut);
        $validar = User::where('rut', $nuevorut)->where('estado', User::USUARIO_ACTVADO)->where('administrador', 1)->first();
        if ($validar && Hash::check($request->password, $validar->password)) {
            // $hoy = Carbon::now();
            // $ufconversion = Ufconversion::where('fecha_conversion', $hoy->format('Y-m-d'))->first();
            // if(empty($ufconversion)){
            //     $client = new Client();
            //     $url = 'https://api.sbif.cl/api-sbifv3/recursos_api/uf?apikey='.env('API_KEY_UF').'&formato=json';
            //     $response = $client->get($url);
            //     if($response->getStatusCode() == 200){
            //         $dato = json_decode($response->getBody());
            //         $valor1 = str_replace(".", "", $dato->UFs[0]->Valor);
            //         Ufconversion::create([
            //             'monto_conversion_original' => $dato->UFs[0]->Valor,
            //             'fecha_conversion' => $dato->UFs[0]->Fecha,
            //             'monto_conversion' => str_replace(",", ".", $valor1),
            //         ]);
            //     }
            // }
            Auth::login($validar);
            return redirect()->route('inicio');
        } else {
            return back()->withErrors(['rut' => 'Estas credenciales no concuerdan con nuestros registros ó el usuario fue desactivado']);
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
}
