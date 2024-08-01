<?php

namespace App\Http\Controllers\Admin\Proyectos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Proyectos\ParceladestroyRequest;
use App\Http\Requests\Admin\Proyectos\ParcelastorageRequest;
use App\Http\Requests\Admin\Proyectos\ParcelaupdateRequest;
use App\Http\Requests\Admin\Proyectos\ProyectoupdateRequest;
use App\Http\Requests\Admin\Proyectos\ProyectodestroyRequest;
use App\Http\Requests\Admin\Proyectos\ProyectostoreRequest;
use App\Models\Estado;
use App\Models\Modulo;
use App\Models\Moneda;
use App\Models\Parcela;
use App\Models\Proyecto;
use App\Models\Submodulo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use NumberFormatter;
use Yajra\DataTables\Facades\DataTables;

class ProyectoController extends Controller
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
        $hoy = Carbon::now();
        $modulos = Modulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $submodulos = Submodulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $proyectos = Proyecto::all();
        $proyectomenus = Proyecto::all();
        return view('pages.admin.proyectos.proyecto.index', compact('hoy', 'modulos', 'submodulos', 'proyectos', 'proyectomenus'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProyectostoreRequest $request)
    {
        DB::transaction(function () use ($request) {
            $proyecto = Proyecto::create([
                'descripcion' => $request->nombre,
                'ubicacion' => $request->ubicacion,
                'imagen' => $request->imagen,
                'numparcelas' => $request->numparcelas,
                'idusuario' => auth()->user()->id,
                'commerce_code' => $request->codigo_comercio
            ]);

            $imagen = Proyecto::where('id', $proyecto->id)->first();
            if ($request->hasFile('imagen')) {
                // $imagen->imagen = 'public/'.$request->file('imagen')->store('proyectos/'.$proyecto->id, 'public');
                $imagen->imagen = $request->file('imagen')->store('proyectos/' . $proyecto->id, 'public');
                $imagen->imagen = str_ireplace("proyectos/{$proyecto->id}/", '', $imagen->imagen);
            }
            $imagen->save();

            if ($proyecto->numparcelas > 0) {
                // $proyectos = Proyecto::all();
                // $numeroproyecto = $proyectos->count();
                $parcelas = array();
                for ($i = 1; $i <= $proyecto->numparcelas; $i++) {
                    // $parcelas[$i]['codigo'] = 'PROY'.($numeroproyecto > 9 ? $numeroproyecto : '0'.$numeroproyecto).'-'.($i > 9 ? $i : '0'.$i);
                    $parcelas[$i]['codigo'] = 'PROY' . ($proyecto->id > 9 ? $proyecto->id : '0' . $proyecto->id) . '-' . ($i > 9 ? $i : '0' . $i);
                    $parcelas[$i]['num_parcela'] = $i;
                    $parcelas[$i]['descripcion'] = 'PARCELA ' . ($i > 9 ? $i : '0' . $i);
                    $parcelas[$i]['idproyecto'] = $proyecto->id;
                    $parcelas[$i]['idestado'] = 1;
                    $parcelas[$i]['created_at'] = Carbon::now();
                    $parcelas[$i]['updated_at'] = Carbon::now();
                }
                Parcela::insert($parcelas);
            }
        });
        $data = Proyecto::all();
        return response()->json($data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        $data = Proyecto::where('id', $request->id)->first();
        return response()->json($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProyectoupdateRequest $request)
    {
        DB::transaction(function () use ($request) {
            $proyecto = Proyecto::where('id', $request->id)->first();
            if ($proyecto->numparcelas > $request->numparcelas) {
                Proyecto::where('id', $request->id)->update([
                    'descripcion' => $request->nombre,
                    'ubicacion' => $request->ubicacion,
                    // 'imagen' => $request->imagen,
                    'numparcelas' => $request->numparcelas,
                    'commerce_code' => $request->codigo_comercio
                ]);
                if ($request->imagen != null) {
                    $imagen = Proyecto::where('id', $request->id)->first();
                    if ($request->hasFile('imagen')) {
                        // $imagen->imagen = 'public/'.$request->file('imagen')->store('proyectos/'.$request->id, 'public');
                        $imagen->imagen = $request->file('imagen')->store('proyectos/' . $request->id, 'public');
                        $imagen->imagen = str_ireplace("proyectos/{$request->id}/", '', $imagen->imagen);
                    }
                    $imagen->save();
                }
                Parcela::where('idproyecto', $request->id)->delete();
                $parcelas = array();
                for ($i = 1; $i <= $proyecto->numparcelas; $i++) {
                    $parcelas[$i]['codigo'] = 'PROY' . ($request->id > 9 ? $request->id : '0' . $request->id) . '-' . ($i > 9 ? $i : '0' . $i);
                    $parcelas[$i]['num_parcela'] = $i;
                    $parcelas[$i]['descripcion'] = 'PARCELA ' . ($i > 9 ? $i : '0' . $i);
                    $parcelas[$i]['idproyecto'] = $request->id;
                    $parcelas[$i]['idestado'] = 1;
                }
                Parcela::insert($parcelas);
            } else if ($proyecto->numparcelas == $request->numparcelas) {
                Proyecto::where('id', $request->id)->update([
                    'descripcion' => $request->nombre,
                    'ubicacion' => $request->ubicacion,
                    'commerce_code' => $request->codigo_comercio,
                    // 'imagen' => $request->imagen,
                    'numparcelas' => $request->numparcelas,
                ]);
                if ($request->imagen != null) {
                    $imagen = Proyecto::where('id', $request->id)->first();
                    if ($request->hasFile('imagen')) {
                        // $imagen->imagen = 'public/'.$request->file('imagen')->store('proyectos/'.$request->id, 'public');
                        $imagen->imagen = $request->file('imagen')->store('proyectos/' . $request->id, 'public');
                        $imagen->imagen = str_ireplace("proyectos/{$request->id}/", '', $imagen->imagen);
                    }
                    $imagen->save();
                }
            } else if ($proyecto->numparcelas < $request->numparcelas) {
                $nuevonumero = $request->numparcelas - $proyecto->numparcelas;
                Proyecto::where('id', $request->id)->update([
                    'descripcion' => $request->nombre,
                    'ubicacion' => $request->ubicacion,
                    // 'imagen' => $request->imagen,
                    'numparcelas' => $request->numparcelas,
                    'commerce_code' => $request->codigo_comercio
                ]);
                if ($request->imagen != null) {
                    $imagen = Proyecto::where('id', $request->id)->first();
                    if ($request->hasFile('imagen')) {
                        // $imagen->imagen = 'public/'.$request->file('imagen')->store('proyectos/'.$request->id, 'public');
                        $imagen->imagen = $request->file('imagen')->store('proyectos/' . $request->id, 'public');
                        $imagen->imagen = str_ireplace("proyectos/{$request->id}/", '', $imagen->imagen);
                    }
                    $imagen->save();
                }
                //
                $ultimaparcela = Parcela::where('idproyecto', $request->id)->orderBy('num_parcela', 'DESC')->first();
                $numero = $ultimaparcela->num_parcela;
                $parcelas = array();
                for ($i = 1; $i <= $nuevonumero; $i++) {
                    $parcelas[$i]['codigo'] = 'PROY' . ($request->id > 9 ? $request->id : '0' . $request->id) . '-' . (($numero + $i) > 9 ? ($numero + $i) : '0' . ($numero + $i));
                    $parcelas[$i]['num_parcela'] = ($numero + $i);
                    $parcelas[$i]['descripcion'] = 'PARCELA ' . (($numero + $i) > 9 ? ($numero + $i) : '0' . ($numero + $i));
                    $parcelas[$i]['idproyecto'] = $request->id;
                    $parcelas[$i]['idestado'] = 1;
                }
                Parcela::insert($parcelas);
            }
        });
        return response()->json();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProyectodestroyRequest $request)
    {
        DB::transaction(function () use ($request) {
            Parcela::where('idproyecto', $request->id)->delete();
            Proyecto::where('id', $request->id)->delete();
        });
        return response()->json();
    }

    public function parcelas($id, Request $request)
    {
        $hoy = Carbon::now();
        $modulos = Modulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $submodulos = Submodulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $proyecto = Proyecto::where('id', $id)->first();
        $monedas = Moneda::all();
        $estados = Estado::all();
        $proyectomenus = Proyecto::all();
        if ($request->ajax()) {
            $data = Parcela::with('estado')->with('moneda')->where('idproyecto', $id)->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('precio', function ($row) {
                    $fmt = numfmt_create('es_CL', NumberFormatter::CURRENCY);
                    return (empty($row->precio_uf) && empty($row->precio_clp)) ? '-' : (empty($row->precio_uf) ? $fmt->formatCurrency($row->precio_clp, "CLP") : str_replace('COL', '', $fmt->formatCurrency($row->precio_uf, "COL")));
                    // return (empty($row->precio_uf) && empty($row->precio_clp)) ? '-' : (empty($row->precio_uf) ? $fmt->formatCurrency($row->precio_clp, "CLP") : $row->precio_uf);
                })
                ->addColumn('moneda', function ($row) {
                    return empty($row->moneda) ? '-' : $row->moneda->descripcion;
                })
                ->addColumn('estado', function ($row) {
                    return '<td class="text-end"><span class="badge py-3 px-4 fs-7 badge-light-' . ($row->idestado == 1 ? 'success' : ($row->idestado == 2 ? 'warning' : 'primary')) . '">' . $row->estado->descripcion . '</span></td>';
                })
                ->addColumn('acciones', function ($row) {
                    return '<div class="d-flex justify-content-center"><a href="javascript:;" class="btn btn-icon btn-warning btn-sm editar-parcela" title="EDITAR" data-id="' . $row->id . '"><i class="ki-duotone ki-pencil"><i class="path1"></i><i class="path2"></i></i></a><a href="javascript:;" class="btn btn-icon btn-danger btn-sm eliminar-parcela" title="ELIMINAR" data-id="' . $row->id . '"><i class="ki-duotone ki-cross-circle"><i class="path1"></i><i class="path2"></i></i></a></div>';
                })
                ->rawColumns(['precio', 'moneda', 'estado', 'acciones'])
                ->make(true);
        }
        return view('pages.admin.proyectos.proyecto.parcelas', compact('hoy', 'modulos', 'submodulos', 'proyecto', 'monedas', 'estados', 'proyectomenus'));
    }

    public function eliminarparcela(ParceladestroyRequest $request)
    {
        DB::transaction(function () use ($request) {
            $parcela = Parcela::where('id', $request->id)->first();
            $proyecto = Proyecto::where('id', $parcela->idproyecto)->first();
            Proyecto::where('id', $proyecto->id)->update([
                'numparcelas' => ($proyecto->numparcelas - 1)
            ]);
            Parcela::where('id', $request->id)->delete();
        });
        return response()->json();
    }

    public function editarparcela(Request $request)
    {
        $data = Parcela::select('*', DB::raw("CASE WHEN idmoneda = 1 then precio_uf else precio_clp end as precio"))->where('id', $request->id)->first();
        return response()->json($data);
    }

    public function actualizarparcela(ParcelaupdateRequest $request)
    {
        Parcela::where('id', $request->id)->update([
            'descripcion' => $request->nombre,
            // 'precio' => $request->precio == '' ? null : $request->precio,
            'idestado' => $request->idestado,
            'idmoneda' => $request->idmoneda,
        ]);
        if (!empty($request->precio)) {
            if ($request->idmoneda == 1) {
                Parcela::where('id', $request->id)->update([
                    'precio_uf' => $request->precio,
                ]);
            } else {
                Parcela::where('id', $request->id)->update([
                    'precio_clp' => $request->precio,
                ]);
            }
        }
        return response()->json();
    }

    public function verimagen($id, $imagen)
    {
        $path = "public/proyectos/{$id}/{$imagen}";
        if (Storage::exists($path)) {
            $image = Storage::get($path);
            return response($image)->header('Content-Type', Storage::mimeType($path));
        }
        return redirect()->route('inicio');
    }

    public function storeparcela(ParcelastorageRequest $request)
    {
        DB::transaction(function () use ($request) {
            $proyecto = Proyecto::where('id', $request->idproyecto)->first();
            $parcela = Parcela::where('idproyecto', $request->idproyecto)->orderBy('num_parcela', 'DESC')->first();
            Parcela::create([
                'codigo' => 'PROY' . ($proyecto->id > 9 ? $proyecto->id : '0' . $proyecto->id) . '-' . (($parcela->num_parcela + 1) > 9 ? ($parcela->num_parcela + 1) : '0' . ($parcela->num_parcela + 1)),
                'num_parcela' => ($parcela->num_parcela + 1),
                'descripcion' => $request->nombre,
                // 'precio' => $request->precio == '' ? null : $request->precio,
                'precio_uf' => ($request->idmoneda == 1 ? $request->precio : null),
                'precio_clp' => ($request->idmoneda == 2 ? $request->precio : null),
                'idproyecto' => $proyecto->id,
                'idestado' => $request->idestado,
                'idmoneda' => $request->idmoneda,
            ]);
            Proyecto::where('id', $request->idproyecto)->update([
                'numparcelas' => ($proyecto->numparcelas + 1)
            ]);
        });
        return response()->json();
    }
}
