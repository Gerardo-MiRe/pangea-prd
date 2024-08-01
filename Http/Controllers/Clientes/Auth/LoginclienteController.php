<?php

namespace App\Http\Controllers\Clientes\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginclienteController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest', ['only' => 'login']);
    }

    public function login()
    {
        $hoy = Carbon::now();
        return view('pages.clientes.auth.login', compact('hoy'));
    }

    public function logearse(LoginRequest $request)
    {
        $nuevorut = str_replace('-', '', $request->rut);
        $validar = User::where('rut', $nuevorut)->where('estado', User::USUARIO_ACTVADO)->where('administrador', 0)->first();
        if ($validar && Hash::check($request->password, $validar->password)) {
            Auth::login($validar);
            return response()->json(1);
        }
        return response()->json(0);
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('logincliente');
    }
}
