<?php

namespace App\Http\Controllers\Admin\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Usuarios\UsuariodestroyRequest;
use App\Http\Requests\Admin\Usuarios\UsuariostoreRequest;
use App\Http\Requests\Admin\Usuarios\UsuarioupdateRequest;
use App\Mail\EnvioclaveMail;
use App\Mail\EnvioTiempo;
use App\Models\Comuna;
use App\Models\Modulo;
use App\Models\Proyecto;
use App\Models\Submodulo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Yajra\DataTables\Facades\DataTables;

class UsuariosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => ['forwardemail3']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $hoy = Carbon::now();
        $modulos = Modulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $submodulos = Submodulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $comunas = Comuna::all();
        $proyectomenus = Proyecto::all();
        if ($request->ajax()) {
            $data = User::all();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('n', function ($row) {
                    return '1';
                })
                ->addColumn('rut', function ($row) {
                    $rutnuevo = str_replace('-', '', $row->rut);
                    return substr($rutnuevo, 0, -1) . '-' . substr($row->rut, -1);
                })
                ->addColumn('estado', function ($row) {
                    return $row->estado == 1 ? 'ACTIVADO' : 'DESACTIVADO';
                })
                ->addColumn('acciones', function ($row) {
                    $acciones = '<a href="javascript:;" class="btn btn-icon btn-primary btn-sm editar-usuarios" title="EDITAR" data-id="' . $row->id . '"><i class="ki-duotone ki-pencil"><i class="path1"></i><i class="path2"></i></i></a><a href="javascript:;" class="btn btn-icon btn-warning btn-sm estado-usuarios" title="ESTADO" data-id="' . $row->id . '"><i class="ki-duotone ki-check-circle"><i class="path1"></i><i class="path2"></i></i></a> <a href="javascript:;" class="btn btn-icon btn-danger btn-sm eliminar-usuarios" title="ELIMINAR" data-id="' . $row->id . '"><i class="ki-duotone ki-cross-circle"><i class="path1"></i><i class="path2"></i></i></a>';
                    if($row->email_verified_at == null){
                        $acciones .= '<a href="javascript:;" class="btn btn-icon fw-bold btn-success btn-sm reenviar-correo" title="REENVIAR CORREO" data-id="' . $row->id . '"><i class="ki-duotone ki-send"><i class="path1"></i><i class="path2"></i></i></a>';
                    }
                    return '<div class="d-flex justify-content-center">' . $acciones . '</div>';
                })
                ->rawColumns(['n', 'rut', 'estado', 'acciones'])
                ->make(true);
        }
        return view('pages.admin.seguridad.usuarios.index', compact('hoy', 'modulos', 'submodulos', 'comunas', 'proyectomenus'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UsuariostoreRequest $request)
    {
        DB::transaction(function () use ($request) {
            $usuario = User::create([
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'email' => $request->email,
                'password' => bcrypt(substr($request->rut, 0, 5)),
                'rut' => str_replace('-', '', $request->rut),
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
                'idcomuna' => $request->idcomuna == 0 ? null : $request->idcomuna,
                'administrador' => $request->administrador == 1 ? User::ADMINISTRADOR : User::NORMAL,
                'estado' => User::USUARIO_ACTVADO,
            ]);

            if ($request->recibir == 1) {
                Mail::to($usuario->email)
                    ->send(new EnvioclaveMail($usuario));
            }
        });
        $usuarios = User::all();
        return response()->json($usuarios);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        $usuario = User::where('id', $request->id)->first();
        $data = [
            'usuario' => $usuario,
        ];
        return response()->json($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UsuarioupdateRequest $request)
    {
        DB::transaction(function () use ($request) {
            if ($request->password == null) {
                User::where('id', $request->id)->update([
                    'nombres' => $request->nombres,
                    'apellidos' => $request->apellidos,
                    'email' => $request->email,
                    'telefono' => $request->telefono,
                    'direccion' => $request->direccion,
                    'email' => $request->email,
                    'idcomuna' => $request->idcomuna == 0 ? null : $request->idcomuna,
                    'administrador' => $request->administrador == 1 ? User::ADMINISTRADOR : User::NORMAL,
                ]);
            } else {
                User::where('id', $request->id)->update([
                    'nombres' => $request->nombres,
                    'apellidos' => $request->apellidos,
                    'password' => bcrypt($request->password),
                    'telefono' => $request->telefono,
                    'email' => $request->email,
                    'direccion' => $request->direccion,
                    'idcomuna' => $request->idcomuna == 0 ? null : $request->idcomuna,
                    'administrador' => $request->administrador == 1 ? User::ADMINISTRADOR : User::NORMAL,
                ]);
            }
        });
        $usuarios = User::all();
        return response()->json($usuarios);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UsuariodestroyRequest $request)
    {
        DB::transaction(function () use ($request) {
            User::where('id', $request->id)->delete();
        });
        return response()->json();
    }

    public function buscarusuarios(Request $request)
    {
        $usuarios = User::select('*', DB::raw("'1' as n"))->nombres($request->nombres)->apellidos($request->apellidos)->email($request->correo)->get();
        return response()->json($usuarios);
    }

    public function estado(Request $request)
    {
        $usuario =  User::where('id', $request->id)->first();
        if ($usuario->estado == 1) {
            User::where('id', $request->id)->update([
                'estado' => User::USUARIO_DESACTIVADO
            ]);
        } else {
            User::where('id', $request->id)->update([
                'estado' => User::USUARIO_ACTVADO
            ]);
        }
        $usuario =  User::where('id', $request->id)->first();
        return response()->json($usuario);
    }
    public function forwardemail(Request $request)
    {
        $usuario = User::where('id', $request->id)->first();
        if ($usuario->email == null) {
            return response()->json(['error' => 'El usuario no tiene correo']);
        }
        if ($usuario == null) {
            return response()->json(['error' => 'El usuario no existe']);
        }
        Mail::to($usuario->email)
            ->send(new EnvioclaveMail($usuario));
        return response()->json();
    }
    public function forwardemail2(Request $request)
    {
        $usuario = User::where('id', $request->id)->first();
        $pagos = DB::table('pagos')
                            ->where('idcliente', $usuario->id)
                            ->get();
        $pagos = $pagos->first();

        if ($usuario->email == null) {
            return response()->json(['error' => 'El usuario no tiene correo']);
        }
        if ($usuario == null) {
            return response()->json(['error' => 'El usuario no existe']);
        }
        Mail::to($usuario->email)
            ->send(new EnvioTiempo($usuario, $pagos));

        // Registro en el historial
        DB::table('historial_envios')->insert([
            'texto' => 'Correo enviado a ' . $usuario->email,
            'usuario' => $usuario->nombres, // O cualquier campo que identifique al usuario
        ]);

        return response()->json();
    }
    public function forwardemail3(Request $request)
    {
        // Obtener los detalles de pago relacionados con los pagos del usuario
        $detallesPagos = DB::table('pagodetalles')
                            ->where('pagado', 0)
                            ->where(function ($query) {
                                $query->whereRaw('DATE_ADD(fecha_vencimiento, INTERVAL 5 DAY) = CURDATE()')
                                      ->orWhereRaw('DATE_ADD(fecha_vencimiento, INTERVAL 15 DAY) = CURDATE()');
                            })
                            ->get();
    
        foreach ($detallesPagos as $detallePago) {
            $pagos = DB::table('pagos')
                    ->where('id', $detallePago->idpago)
                    ->get()->first();
            
            $usuario = User::where('id', $pagos->idcliente)->first();
            
            $parcela = DB::table('parcelas')
                    ->where('id', $pagos->idparcela)
                    ->get()->first();
                    
            DB::table('historial_envios')->insert([
                'texto' => 'Correo enviado: Tienes un pago venciodo del día: ' . $detallePago->fecha_vencimiento . "De la parcela: " . $parcela->codigo,
                'usuario' => $usuario->nombres,
            ]);
        }
    }
}
