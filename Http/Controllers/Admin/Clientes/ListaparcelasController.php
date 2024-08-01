<?php

namespace App\Http\Controllers\Admin\Clientes;

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

class ListaparcelasController extends Controller
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
        $hoy = Carbon::now();
        $modulos = Modulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $submodulos = Submodulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $estados = Estado::all();
        $proyectomenus = Proyecto::all();
        if ($request->ajax()) {
            $data = Pago::with('parcela')->with('cliente')->where('idcliente', auth()->user()->id)->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('codigo', function ($row) {
                    return $row->parcela->codigo;
                })
                ->addColumn('moneda', function ($row) {
                    return $row->parcela->moneda->descripcion;
                })
                ->addColumn('preciototal', function ($row) {
                    $fmt = numfmt_create('es_CL', NumberFormatter::CURRENCY);
                    // return (empty($row->parcela->precio_uf) ? $fmt->formatCurrency($row->parcela->precio_clp, "CLP") : $row->parcela->precio_uf);
                    return (empty($row->parcela->precio_uf) ? $fmt->formatCurrency($row->parcela->precio_clp, "CLP") : str_replace('COL', '', $fmt->formatCurrency($row->parcela->precio_uf, "COL")));
                })
                ->addColumn('monto_porcentaje', function ($row) {
                    return $row->monto_inicial_porcentaje . '%';
                })
                ->addColumn('monto_inicial_dinero', function ($row) {
                    $fmt = numfmt_create('es_CL', NumberFormatter::CURRENCY);
                    // return (empty($row->monto_inicial_dinero_uf) ? $fmt->formatCurrency($row->monto_inicial_dinero_clp, "CLP") : $row->monto_inicial_dinero_uf);
                    return (empty($row->monto_inicial_dinero_uf) ? $fmt->formatCurrency($row->monto_inicial_dinero_clp, "CLP") : str_replace('COL', '', $fmt->formatCurrency($row->monto_inicial_dinero_uf, "COL")));
                })
                ->addColumn('fechapago', function ($row) {
                    return $row->created_at->format('d/m/Y');
                })
                ->addColumn('saldopendiente', function ($row) {
                    $fmt = numfmt_create('es_CL', NumberFormatter::CURRENCY);
                    // return (empty($row->monto_total_restante_uf) ? $fmt->formatCurrency(($row->monto_total_restante_clp > 0 ? $row->monto_total_restante_clp : 0), "CLP") : ($row->monto_total_restante_uf > 0 ? $row->monto_total_restante_uf : 0));
                    return (empty($row->monto_total_restante_uf) ? $fmt->formatCurrency(($row->monto_total_restante_clp > 0 ? $row->monto_total_restante_clp : 0), "CLP") : ($row->monto_total_restante_uf > 0 ? str_replace('COL', '', $fmt->formatCurrency($row->monto_total_restante_uf, "COL")) : 0));
                })
                ->addColumn('cliente', function ($row) {
                    return $row->cliente->apellidos . ' ' . $row->cliente->nombres;
                })
                ->addColumn('rut', function ($row) {
                    $rutnuevo = str_replace('-', '', $row->cliente->rut);
                    return substr($rutnuevo, 0, -1) . '-' . substr($row->cliente->rut, -1);
                })
                ->addColumn('estado', function ($row) {
                    return '<td class="text-end"><span class="badge py-3 px-4 fs-7 badge-light-' . ($row->parcela->idestado == 1 ? 'success' : ($row->parcela->idestado == 2 ? 'warning' : 'primary')) . '">' . $row->parcela->estado->descripcion . '</span></td>';
                })
                ->addColumn('acciones', function ($row) {
                    // return '<div class="d-flex justify-content-center"><a href="javascript:;" class="btn btn-icon btn-primary btn-sm editar-pago" title="EDITAR" data-id="'.$row->id.'"><i class="ki-duotone ki-pencil"><i class="path1"></i><i class="path2"></i></i></a><a href="javascript:;" class="btn btn-icon btn-warning btn-sm estado-pago" title="ESTADO" data-id="'.$row->id.'"><i class="ki-duotone ki-check-circle"><i class="path1"></i><i class="path2"></i></i></a> <a href="javascript:;" class="btn btn-icon btn-danger btn-sm eliminar-pago" title="ELIMINAR" data-id="'.$row->id.'"><i class="ki-duotone ki-cross-circle"><i class="path1"></i><i class="path2"></i></i></a></div>';
                    return '<div class="d-flex justify-content-center"><a href="' . route('clientes.lista.consolidados', $row->parcela->codigo) . '" class="btn btn-icon btn-primary btn-sm editar-pago" title="VER" data-id="' . $row->id . '"><i class="ki-duotone ki-eye"><i class="path1"></i><i class="path2"><i class="path3"></i></i></i></a></div>';
                })
                ->rawColumns(['codigo', 'moneda', 'preciototal', 'monto_porcentaje', 'monto_inicial_dinero', 'fechapago', 'saldopendiente', 'cliente', 'rut', 'estado', 'acciones'])
                ->make(true);
        }
        return view('pages.admin.clientes.lista.index', compact('hoy', 'modulos', 'submodulos', 'estados', 'proyectomenus'));
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

    public function consolidados($codigo, Request $request)
    {
        $hoy = Carbon::now();
        $modulos = Modulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $submodulos = Submodulo::where('estado', 1)->orderBy('orden', 'asc')->get();
        $metodopagos = Metodopago::all();
        //TOTALES
        $parcela = Parcela::where('codigo', $codigo)->first();
        $pago = Pago::with('cliente')->where('idparcela', $parcela->id)->first();
        $monto = $parcela->idmoneda == 1 ? 'monto_cuota_uf' : 'monto_cuota_clp';
        $fmt = numfmt_create('es_CL', NumberFormatter::CURRENCY);
        $totalpagados = $parcela->idmoneda == 1 ? str_replace('COL', '', $fmt->formatCurrency(Pagodetalle::where('pagado', 1)->where('idpago', $pago->id)->sum($monto), "COL")) : $fmt->formatCurrency((Pagodetalle::where('pagado', 1)->where('idpago', $pago->id)->sum($monto)), "CLP");
        $totalporpagar = $parcela->idmoneda == 1 ? str_replace('COL', '', $fmt->formatCurrency(Pagodetalle::where('pagado', 0)->where('idpago', $pago->id)->sum($monto), "COL")) : $fmt->formatCurrency((Pagodetalle::where('pagado', 0)->where('idpago', $pago->id)->sum($monto)), "CLP");
        $cuotaspagadas = Pagodetalle::where('pagado', 1)->where('idpago', $pago->id)->count();
        $cuotasporpagar = Pagodetalle::where('pagado', 0)->where('idpago', $pago->id)->count();
        $proyectomenus = Proyecto::all();
        if ($request->ajax()) {
            // $parcela = Parcela::with('moneda')->where('codigo', $codigo)->first();
            // $pago = Pago::where('idparcela', $parcela->id)->first();
            // $data = Pagodetalle::with('pago')->with('metodopago')->where('idpago', $pago->id)->get();
            $data = Pagodetalle::listarconsolidados($codigo);
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('moneda', function ($row) {
                    // return $row->idmoneda == null ? '-' : $row->moneda->descripcion;
                    // return $parcela->moneda->descripcion;
                    return $row->moneda;
                })
                ->addColumn('monto_cuota', function ($row) {
                    $fmt = numfmt_create('es_CL', NumberFormatter::CURRENCY);
                    // return $row->idmoneda == 1 ? $row->monto_cuota_uf : $fmt->formatCurrency($row->monto_cuota_clp, "CLP");
                    return $row->idmoneda == 1 ? str_replace('COL', '', $fmt->formatCurrency($row->monto_cuota_uf, "COL")) : $fmt->formatCurrency($row->monto_cuota_clp, "CLP");
                })
                ->addColumn('interes', function ($row) {
                    $fmt = numfmt_create('es_CL', NumberFormatter::CURRENCY);
                    // return $row->idmoneda == 1 ? $row->interes_pagado_uf : $fmt->formatCurrency($row->interes_pagado_clp, "CLP");
                    return $row->idmoneda == 1 ? str_replace('COL', '', $fmt->formatCurrency($row->interes_pagado_uf, "COL")) : $fmt->formatCurrency($row->interes_pagado_clp, "CLP");
                })
                ->addColumn('estado', function ($row) {
                    return '<td class="text-end"><span class="badge py-3 px-4 fs-7 badge-light-' . ($row->pagado == 1 ? 'success' : 'warning') . '">' . ($row->pagado == 1 ? 'PAGADA' : 'PENDIENTE') . '</span></td>';
                })
                ->addColumn('fechavencimiento', function ($row) {
                    // $orgDate = $row->fecha_pago;
                    $orgDate = $row->fecha_vencimiento;
                    $newDate = date("d/m/Y", strtotime($orgDate));
                    // return $row->fecha_pago == null ? '-' : $newDate;
                    return $newDate;
                })
                ->addColumn('fechapago', function ($row) {
                    // $orgDate = $row->fecha_pago;
                    $orgDate = $row->fecha_pago;
                    $newDate = date("d/m/Y", strtotime($orgDate));
                    // return $row->fecha_pago == null ? '-' : $newDate;
                    return $row->fecha_pago == null ? '-' : $newDate;
                })
                ->addColumn('metodopago', function ($row) {
                    return $row->idmetodopago == null ? '-' : $row->metodopago->descripcion;
                })
                ->addColumn('saldo', function ($row) {
                    $fmt = numfmt_create('es_CL', NumberFormatter::CURRENCY);
                    $montototal = $row->idmoneda == 1 ? $row->monto_restante_uf : $row->monto_restante_clp;
                    $saldo = $row->idmoneda == 1 ? ($montototal < 0 ? 0 : $montototal) : $fmt->formatCurrency(($montototal < 0 ? 0 : $montototal), "CLP");
                    return $saldo == '' ? '-' : $saldo;
                })
                ->addColumn('acciones', function ($row) {
                    // return '<div class="d-flex justify-content-center"><a href="javascript:;" class="btn btn-icon btn-primary btn-sm editar-pago mr-2" title="EDITAR" data-id="'.$row->id.'"><i class="ki-duotone ki-pencil"><i class="path1"></i><i class="path2"></i></i></a><a href="javascript:;" class="btn btn-icon btn-warning btn-sm editar-refinanciar ml-2" title="REFINANCIAR" data-id="'.$row->id.'"><i class="ki-duotone ki-finance-calculator"><i class="path1"></i><i class="path2"></i><i class="path3"></i><i class="path4"></i><i class="path5"></i><i class="path6"></i><i class="path7"></i></i></a></div>';
                    return '<div class="d-flex justify-content-center"><a href="javascript:;" class="btn btn-icon btn-primary btn-sm editar-consolidado mr-2" title="EDITAR" data-moneda="' . $row->moneda . '" data-id="' . $row->id . '"><i class="ki-duotone ki-pencil"><i class="path1"></i><i class="path2"></i></i></a></div>';
                })
                ->rawColumns(['moneda', 'estado', 'fechavencimiento', 'metodopago', 'saldo', 'acciones', 'interes', 'fechapago'])
                ->make(true);
        }
        return view('pages.admin.clientes.lista.consolidados', compact('hoy', 'modulos', 'submodulos', 'codigo', 'totalpagados', 'totalporpagar', 'cuotaspagadas', 'cuotasporpagar', 'metodopagos', 'proyectomenus', 'pago', 'parcela'));
    }

    public function cuota(Request $request)
    {
        $data = [];
        DB::transaction(function () use ($request, &$data) {
            // $parcela = Parcela::with('moneda')->where('codigo', $request->id)->first();
            $pagodetalle = Pagodetalle::select('*', DB::raw("DATE_FORMAT(fecha_pago, '%Y-%m-%d') as FF"))->where('id', $request->id)->first();
            $pago = Pago::with('parcela')->where('id', $pagodetalle->idpago)->first();
            $fmt = numfmt_create('es_CL', NumberFormatter::CURRENCY);
            $monto = $pago->parcela->idmoneda == 1 ? $pagodetalle->monto_cuota_uf : $fmt->formatCurrency($pagodetalle->monto_cuota_clp, "CLP");
            $hoy = Carbon::now();
            // $ufconversion = Ufconversion::where('fecha_conversion', $hoy->format('Y-m-d'))->first();
            // $ufconversion = Ufconversion::orderBy('id', 'desc')->first();
            //
            // $montoconversion = $pagodetalle->idmetodopago == null ? 0 : (($pagodetalle->idmetodopago == 1 && $pago->parcela->idmoneda == 1) ? )
            $montoconversion = 0;
            if ($pagodetalle->idmetodopago == 1 && $pago->parcela->idmoneda == 1) {
                $montoconversion = $pagodetalle->monto_cuota_pagado_uf_conversion_clp;
            } else if ($pagodetalle->idmetodopago == 1 && $pago->parcela->idmoneda == 2) {
                $montoconversion = $pagodetalle->monto_cuota_pagado_clp;
            } else if ($pagodetalle->idmetodopago == 2 && $pago->parcela->idmoneda == 1) {
                $montoconversion = $pagodetalle->monto_cuota_pagado_uf_conversion_clp + $pagodetalle->interes_pagado_uf_conversion_clp;
            } else if ($pagodetalle->idmetodopago == 2 && $pago->parcela->idmoneda == 2) {
                $montoconversion = $pagodetalle->monto_cuota_pagado_clp + $pagodetalle->interes_pagado_clp;
            } else {
                $montoconversion = 0;
            }
            $data = [
                'data1' => $pagodetalle,
                'data2' => $pago,
                'data3' => $monto,
                // 'data4' => ($pagodetalle->monto_cuota_pagado_uf_conversion_clp == null ? $fmt->formatCurrency(($ufconversion->monto_conversion * $pagodetalle->monto_cuota_uf), "CLP") : $fmt->formatCurrency(($pagodetalle->monto_cuota_pagado_uf_conversion_clp), "CLP")),
                'data4' => $fmt->formatCurrency(($montoconversion), "CLP")
            ];
        });
        return response()->json($data);
    }

    public function conversor(Request $request)
    {
        $pagodetalle = Pagodetalle::where('id', $request->id)->first();
        $pago = Pago::with('parcela')->where('id', $pagodetalle->idpago)->first();
        $ufconversion = Ufconversion::orderBy('id', 'desc')->first();
        $montoconversion = 0;
        if ($request->idmetodopago == 1 && $pago->parcela->idmoneda == 1) {
            $montoconversion = $ufconversion->monto_conversion * $pagodetalle->monto_cuota_uf;
        } else if ($request->idmetodopago == 1 && $pago->parcela->idmoneda == 2) {
            $montoconversion = $pagodetalle->monto_cuota_clp;
        } else if ($request->idmetodopago == 2 && $pago->parcela->idmoneda == 1) {
            $monto = $ufconversion->monto_conversion * $pagodetalle->monto_cuota_uf;
            $montoconversion = $monto + ($monto * 0.03);
        } else if ($request->idmetodopago == 2 && $pago->parcela->idmoneda == 2) {
            $montoconversion = $pagodetalle->monto_cuota_clp + ($pagodetalle->monto_cuota_clp * 0.03);
        }
        $fmt = numfmt_create('es_CL', NumberFormatter::CURRENCY);
        return response()->json($fmt->formatCurrency(($montoconversion), "CLP"));
    }

    // public function storecuota(CuotastoreRequest $request)
    // {
    //     DB::transaction(function() use ($request){
    //         $pagodetalleinicio = Pagodetalle::where('id', $request->id)->first();
    //         $pago = Pago::with('parcela')->where('id', $pagodetalleinicio->idpago)->first();
    //         if($pagodetalleinicio->pagado == 0){
    //             $hoy = Carbon::now();
    //             $ufconversion = Ufconversion::where('fecha_conversion', $hoy->format('Y-m-d'))->first();
    //             $montocuotaconversion = $ufconversion->monto_conversion * $pagodetalleinicio->monto_cuota_uf;
    //             Pagodetalle::where('id', $request->id)->update([
    //                 'idmetodopago' => $request->idmetodopago,
    //                 'pagado' => 1,
    //                 'fecha_pago' => $request->fecha,
    //                 'monto_cuota_pagado_uf' => ($pago->parcela->idmoneda == 1 ? $pagodetalleinicio->monto_cuota_uf : null),
    //                 'monto_cuota_pagado_clp' => ($pago->parcela->idmoneda == 2 ? $pagodetalleinicio->monto_cuota_clp : null),
    //                 'monto_restante_uf' => ($pago->parcela->idmoneda == 1 ? ($pago->monto_total_restante_uf - ($pagodetalleinicio->monto_cuota_uf == null ? 0 : $pagodetalleinicio->monto_cuota_uf)) : null),
    //                 'monto_restante_clp' => ($pago->parcela->idmoneda == 2 ? ($pago->monto_total_restante_clp - ($pagodetalleinicio->monto_cuota_clp == null ? 0 : $pagodetalleinicio->monto_cuota_clp)) : null),
    //                 // 'interes_pagado_uf' => (($request->idmetodopago == 1 && $pago->parcela->idmoneda == 1) ? ($request->monto - $pagodetalleinicio->monto_cuota_uf) : null),
    //                 // 'interes_pagado_clp' => (($request->idmetodopago == 1 && $pago->parcela->idmoneda == 2) ? ($request->monto - $pagodetalleinicio->monto_cuota_clp) : null),
    //                 'interes_pagado_uf' => (($request->idmetodopago == 2 && $pago->parcela->idmoneda == 1) ? ($pagodetalleinicio->monto_cuota_uf * 0.03) : null),
    //                 'interes_pagado_clp' => (($request->idmetodopago == 2 && $pago->parcela->idmoneda == 2) ? ($pagodetalleinicio->monto_cuota_clp *0.03) : null),
    //                 'num_transaccion' => ($request->idmetodopago == 1 ? $request->numtransaccion : null),
    //                 'observacion' => ($request->idmetodopago == 1 ? $request->observacion : null),
    //                 //NUEVO
    //                 'monto_cuota_pagado_uf_conversion_clp' => ($pago->parcela->idmoneda == 1 ? $montocuotaconversion : null),
    //                 'interes_pagado_uf_conversion_clp' => (($request->idmetodopago == 2 && $pago->parcela->idmoneda == 1) ? ($montocuotaconversion * 0.03) : null),
    //                 'fecha_pago_uf_conversion_clp' => ($pago->parcela->idmoneda == 1 ? $ufconversion->fecha_conversion : null),
    //                 'idclientepago' => auth()->user()->id,
    //             ]);

    //             $pagodetalle = Pagodetalle::where('id', $request->id)->first();
    //             Pago::where('id', $pagodetalle->idpago)->update([
    //                 'monto_total_restante_uf' => $pagodetalle->monto_restante_uf,
    //                 'monto_total_restante_clp' => $pagodetalle->monto_restante_clp,
    //             ]);

    //             $pagoactual = Pago::with('parcela')->where('id', $pagodetalleinicio->idpago)->first();
    //             $montorestante = $pagoactual->parcela->idmoneda == 1 ? $pagoactual->monto_total_restante_uf : $pagoactual->monto_total_restante_clp;
    //             if($montorestante <= 0){
    //                 Parcela::where('id', $pago->idparcela)->update([
    //                     'idestado' => 3
    //                 ]);
    //             }
    //         }
    //     });
    //     return response()->json();
    // }

    public function storecuota(CuotastoreRequest $request)
    {
        $data = [];
        // $response = (new Transaction);
        DB::transaction(function () use ($request, &$data, &$response) {
            // try {
            $pagodetalleinicio = Pagodetalle::where('id', $request->id)->first();
            $pago = Pago::with('parcela')->where('id', $pagodetalleinicio->idpago)->first();
            if ($pagodetalleinicio->pagado == 0) {
                $hoy = Carbon::now();
                // $ufconversion = Ufconversion::where('fecha_conversion', $hoy->format('Y-m-d'))->first();
                $ufconversion = Ufconversion::orderBy('id', 'desc')->first();
                $montocuotaconversion = $ufconversion->monto_conversion * $pagodetalleinicio->monto_cuota_uf;
                // dd($montocuotaconversion); //1.000.000 esta cantidad maximo acepta
                $monto = ($pago->parcela->idmoneda == 1 ? $montocuotaconversion : $pagodetalleinicio->monto_cuota_clp);
                // $monto = ($pago->parcela->idmoneda == 1 ? ($montocuotaconversion + ($montocuotaconversion * 0.03)) : $pagodetalleinicio->monto_cuota_clp);
                // dd(round($monto));
                $response = (new Transaction)->create(round($monto), $request->id, auth()->user()->id);
            }
            // } catch (WebpayRequestException $e) {
            //     $e;
            // }
            $data = [
                'token' => $response->getToken(),
                'response' => json_decode(json_encode($response), true),
                'request' => $request->except('_token')
            ];
        });
        return response()->json($data);
    }

    public function commit(Request $request)
    {
        $this->validate($request, [
            'token' => 'required'
        ]);
        $response = (new Transaction)->commit($request->get('token'));
        if ($response->status == 'AUTHORIZED') {

            DB::transaction(function () use ($request, &$response) {
                Transbank::create([
                    'status' => $response->status,
                    'responsecode' => $response->responseCode,
                    'authorizationcode' => $response->authorizationCode,
                    'paymenttypecode' => $response->paymentTypeCode,
                    'accountingdate' => $response->accountingDate,
                    'amount' => $response->amount,
                    'installmentsnumber' => $response->installmentsNumber,
                    'installmentsamount' => $response->installmentsAmount,
                    'sessionid' => $response->sessionId,
                    'buyorder' => $response->buyOrder,
                    'cardnumber' => $response->cardNumber,
                    // 'carddetail' => null,
                    'transactiondate' => $response->transactionDate,
                    'balance' => $response->balance,
                ]);

                $pagodetalleinicio = Pagodetalle::where('id', $request->id)->first();
                $pago = Pago::with('parcela')->where('id', $pagodetalleinicio->idpago)->first();
                if ($pagodetalleinicio->pagado == 0) {
                    $hoy = Carbon::now();
                    $ufconversion = Ufconversion::where('fecha_conversion', $hoy->format('Y-m-d'))->first();
                    $montocuotaconversion = $ufconversion->monto_conversion * $pagodetalleinicio->monto_cuota_uf;
                    $pagodetalleinicio->update([
                        'idmetodopago' => $request->idmetodopago,
                        'pagado' => 1,
                        'fecha_pago' => Carbon::now(), //$request->fecha,
                        'monto_cuota_pagado_uf' => ($pago->parcela->idmoneda == 1 ? $pagodetalleinicio->monto_cuota_uf : null),
                        'monto_cuota_pagado_clp' => ($pago->parcela->idmoneda == 2 ? $pagodetalleinicio->monto_cuota_clp : null),
                        'monto_restante_uf' => ($pago->parcela->idmoneda == 1 ? ($pago->monto_total_restante_uf - ($pagodetalleinicio->monto_cuota_uf == null ? 0 : $pagodetalleinicio->monto_cuota_uf)) : null),
                        'monto_restante_clp' => ($pago->parcela->idmoneda == 2 ? ($pago->monto_total_restante_clp - ($pagodetalleinicio->monto_cuota_clp == null ? 0 : $pagodetalleinicio->monto_cuota_clp)) : null),
                        // 'interes_pagado_uf' => (($request->idmetodopago == 1 && $pago->parcela->idmoneda == 1) ? ($request->monto - $pagodetalleinicio->monto_cuota_uf) : null),
                        // 'interes_pagado_clp' => (($request->idmetodopago == 1 && $pago->parcela->idmoneda == 2) ? ($request->monto - $pagodetalleinicio->monto_cuota_clp) : null),
                        'interes_pagado_uf' => (($request->idmetodopago == 2 && $pago->parcela->idmoneda == 1) ? ($pagodetalleinicio->monto_cuota_uf * 0.03) : null),
                        'interes_pagado_clp' => (($request->idmetodopago == 2 && $pago->parcela->idmoneda == 2) ? ($pagodetalleinicio->monto_cuota_clp * 0.03) : null),
                        // 'num_transaccion' => ($request->idmetodopago == 1 ? $request->numtransaccion : null),
                        // 'observacion' => ($request->idmetodopago == 1 ? $request->observacion : null),
                        //NUEVO
                        'monto_cuota_pagado_uf_conversion_clp' => ($pago->parcela->idmoneda == 1 ? $montocuotaconversion : null),
                        'interes_pagado_uf_conversion_clp' => (($request->idmetodopago == 2 && $pago->parcela->idmoneda == 1) ? ($montocuotaconversion * 0.03) : null),
                        'fecha_pago_uf_conversion_clp' => ($pago->parcela->idmoneda == 1 ? $ufconversion->fecha_conversion : null),
                        'idclientepago' => auth()->user()->id,
                    ]);

                    $pagodetalle = Pagodetalle::where('id', $request->id)->first();
                    Pago::where('id', $pagodetalle->idpago)->update([
                        'monto_total_restante_uf' => $pagodetalle->monto_restante_uf,
                        'monto_total_restante_clp' => $pagodetalle->monto_restante_clp,
                    ]);

                    $pagoactual = Pago::with('parcela')->where('id', $pagodetalleinicio->idpago)->first();
                    $montorestante = $pagoactual->parcela->idmoneda == 1 ? $pagoactual->monto_total_restante_uf : $pagoactual->monto_total_restante_clp;
                    if ($montorestante <= 0) {
                        Parcela::where('id', $pago->idparcela)->update([
                            'idestado' => 3
                        ]);
                    }
                }
            });
        }
        return response()->json($response);
    }
}
