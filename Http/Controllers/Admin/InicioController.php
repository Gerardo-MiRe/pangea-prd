<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MiperfilRequest;
use App\Models\Modulo;
use App\Models\Parcela;
use App\Models\Proyecto;
use App\Models\Submodulo;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class InicioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $modulos = Modulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $submodulos = Submodulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $hoy = Carbon::now();
        $usuarios = auth()->user()->administrador == 1 ? User::count() : 0;
        $proyectos = auth()->user()->administrador == 1 ? Proyecto::count() : 0;
        $parcelas = auth()->user()->administrador == 1 ? Parcela::count() : Parcela::where('idusuario', auth()->user()->id)->count();
        $proyectomenus = Proyecto::all();
        return view('pages.admin.index', compact('hoy', 'modulos', 'submodulos', 'usuarios', 'proyectos', 'parcelas', 'proyectomenus'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function actualizarmiperfil(MiperfilRequest $request)
    {
        if ($request->password == null) {
            User::where('id', auth()->user()->id)->update([
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
            ]);
        } else {
            User::where('id', auth()->user()->id)->update([
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'password' => bcrypt($request->password),
            ]);
        }
        return response()->json();
    }
}
