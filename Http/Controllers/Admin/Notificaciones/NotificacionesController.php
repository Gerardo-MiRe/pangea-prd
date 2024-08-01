<?php

namespace App\Http\Controllers\Admin\Notificaciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Pagos\Lista\CuotastoreRequest;
use App\Models\Estado;
use App\Models\Metodopago;
use App\Models\Modulo;
use App\Models\Pago;
use App\Models\Pagodetalle;
use App\Models\Parcela;
use App\Models\Proyecto;
use App\Models\Submodulo;
use App\Models\Transbank;
use App\Models\Ufconversion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use NumberFormatter;
use Yajra\DataTables\Facades\DataTables;
use Transbank\Webpay\Exceptions\WebpayRequestException;
use Transbank\Webpay\Modal\Exceptions\TransactionRefundException;
use Transbank\Webpay\Modal\Transaction;

class NotificacionesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Usando el constructor de consultas
        $vencidos = DB::table('vencidos')->get();
        $hoy = Carbon::now();
        $modulos = Modulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $submodulos = Submodulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $estados = Estado::all();
        $proyectomenus = Proyecto::all();
        return view('pages.admin.notificaciones.lista.index', compact('hoy', 'modulos', 'submodulos', 'estados','vencidos','proyectomenus'));
    }
    
    public function index2(Request $request)
    {
        // Usando el constructor de consultas
        $historialEnvios = DB::table('historial_envios')->orderBy('fecha', 'desc')->get();
        $hoy = Carbon::now();
        $modulos = Modulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $submodulos = Submodulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $estados = Estado::all();
        $proyectomenus = Proyecto::all();
        return view('pages.admin.notificaciones.lista.tabla', compact('hoy', 'modulos', 'submodulos', 'estados','historialEnvios','proyectomenus'));
    }
    
    public function updateVencidos(Request $request)
    {
        // Validar los datos del request
        $validatedData = $request->validate([
            'rango' => 'required|integer|min:1',
            'rango2' => 'required|integer|min:1',
        ]);
    
        // Actualizar los valores en la tabla 'vencidos'
        DB::table('vencidos')
        ->where('id', 1)  // Reemplaza '1' con el valor correcto para identificar el registro
        ->update([
            'rango' => $validatedData['rango'],
            'rango2' => $validatedData['rango2'],
        ]);
    
        // Obtener los registros actualizados
        $vencidos = DB::table('vencidos')->get();
    
        // Obtener otros datos necesarios para la vista
        $hoy = Carbon::now();
        $modulos = Modulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $submodulos = Submodulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $estados = Estado::all();
        $proyectomenus = Proyecto::all();
    
        // Retornar la vista con los datos
        return view('pages.admin.notificaciones.lista.index', compact('hoy', 'modulos', 'submodulos', 'estados', 'vencidos', 'proyectomenus'));
    }
    

}