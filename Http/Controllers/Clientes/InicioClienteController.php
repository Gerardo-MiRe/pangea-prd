<?php

namespace App\Http\Controllers\Clientes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\ConsultardeudaRequest;
use App\Http\Requests\Cliente\PagartransbankRequest;
use App\Models\Pago;
use App\Models\Pagodetalle;
use App\Models\Parcela;
use App\Models\Proyecto;
use App\Models\Transbank;
use App\Models\Ufconversion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use NumberFormatter;
use Transbank\Webpay\WebpayPlus;
use Yajra\DataTables\DataTables;

class InicioClienteController extends Controller
{
    public function __construct()
    {
        // $this->middleware('authcliente', ['only' => 'index']);
        //$this->middleware('guest', ['only' => 'listadeuda']);

        if (app()->environment('production')) {
            WebpayPlus::configureForProduction(config('app.transbank.webpay_plus_cc'), config('app.transbank.webpay_plus_api_key'));
        } else {
            WebpayPlus::configureForTestingMall();
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $hoy = Carbon::now();
        $parcela = Parcela::where('idusuario', auth()->user()->id)->orderBy('id', 'desc')->first(); //->where('idestado', 2)
        if (!empty($parcela)) {
            $moneda = $parcela->idmoneda;
            $pago = Pago::select('id')->where('idparcela', $parcela->id)->first();
            if ($request->ajax()) {
                $data = Pagodetalle::listardeudascliente($parcela->id);
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('seleccionar', function ($row) {
                        $ufconversion = Ufconversion::orderBy('id', 'desc')->first();
                        return $row->id . '-' . ($row->idmoneda == 1 ? ($ufconversion->monto_conversion * $row->monto_cuota_uf) : $row->monto_cuota_clp) . '-' . $row->n . '-' . $row->pagado;
                    })
                    ->addColumn('monto_cuota', function ($row) {
                        $fmt = numfmt_create('es_CL', NumberFormatter::CURRENCY);
                        return $row->idmoneda == 1 ? 'UF' . (str_replace('COL', '', $fmt->formatCurrency($row->monto_cuota_uf, "COL"))) : $fmt->formatCurrency($row->monto_cuota_clp, "CLP");
                    })
                    ->addColumn('estado2', function ($row) {
                        return '<td class="text-end"><span class="badge py-3 px-4 fs-7 badge-light-' . ($row->pagado == 1 ? 'primary' : ($row->estado == 'Por Vencer' ? 'success' : 'danger')) . '">' . ($row->pagado == 1 ? 'Pagado' : $row->estado) . '</span></td>';
                    })
                    ->addColumn('fechavencimiento', function ($row) {
                        $orgDate = $row->fecha_vencimiento;
                        $newDate = date("d/m/Y", strtotime($orgDate));
                        return $newDate;
                    })
                    ->addColumn('fechapago', function ($row) {
                        $orgDate = $row->fecha_pago;
                        $newDate = $orgDate == null ? '-' : date("d/m/Y", strtotime($orgDate));
                        return $newDate;
                    })
                    ->addColumn('mediopago', function ($row) {
                        return $row->metodo == null ? '-' : $row->metodo;
                    })
                    ->addColumn('acciones', function ($row) {
                        return $row->pagado == 1 ? '<div class="d-flex justify-content-center"><a href="javascript:;" class="btn btn-icon btn-danger btn-sm" title="VER"><i class="ki-duotone ki-document fs-3"><span class="path1"></span><span class="path2"></span></i></a></div>' : '-';
                    })
                    ->rawColumns(['seleccionar', 'monto_cuota', 'estado2', 'fechavencimiento', 'fechapago', 'mediopago', 'acciones'])
                    ->make(true);
            }
            return view('pages.clientes.index', compact('hoy', 'parcela', 'moneda', 'pago', 'request'));
        } else {
            return view('pages.clientes.index', compact('hoy', 'parcela'));
        }
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

    public function consultardeuda(ConsultardeudaRequest $request)
    {
        $nuevorut = str_replace('-', '', $request->rut);
        $usuario = User::where('rut', $nuevorut)->where('administrador', 0)->first();
        $parcela = Parcela::where('idusuario', $usuario->id)->where('idestado', 2)->orderBy('id', 'desc')->first();
        if (!empty($parcela)) {
            return response()->json(Crypt::encrypt($usuario->id));
        } else {
            return response()->json(0);
        }
    }

    public function listadeuda(Request $request, $id = null)
    {
        $hoy = Carbon::now();
        if ($id) {
            $usuario = User::where('id', Crypt::decrypt($id))->where('administrador', 0)->first();
        } else {
            $usuario  = auth()->user();
        }

        $parcelas = Parcela::where('idusuario', $usuario->id)->where('idestado', 2)->orderBy('id', 'desc')->get();
        $monedas = [];
        $pagos = [];

        foreach ($parcelas as $parcela) {
            $monedas[$parcela->id] = $parcela->idmoneda;
            $pagos[$parcela->id] = Pago::select('id')->where('idparcela', $parcela->id)->first();
        }

        $valorUF = Ufconversion::orderBy('id', 'desc')->first()->monto_conversion;

        return view('pages.clientes.listadeuda', compact('hoy', 'usuario', 'parcelas', 'monedas', 'pagos', 'request', 'valorUF'));
    }

    public function datatablecliente($id, Request $request)
    {
        if (auth()->user()) {
            $parcela = Parcela::find($id);
            $data = Pagodetalle::listardeudascliente($parcela->id);
        } else {
            $parcela = Parcela::find($id);
            $cuotas = Pagodetalle::listardeudasolorut($parcela->id);
            $data = [];
            foreach ($cuotas as $cuota) {
                $data[] = $cuota;
                if ($cuota->estado == 'Por Vencer') {
                    break;
                }
            }
        }

        $datable = DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('seleccionar', function ($row) {
                $ufconversion = Ufconversion::orderBy('id', 'desc')->first();
                return $row->id . '-' . ($row->idmoneda == 1 ? ($ufconversion->monto_conversion * $row->monto_cuota_uf) : $row->monto_cuota_clp) . '-' . $row->n . '-' . $row->pagado;
            })
            ->addColumn('monto_cuota', function ($row) {
                $fmt = numfmt_create('es_CL', NumberFormatter::CURRENCY);
                return $row->idmoneda == 1 ? 'UF' . (str_replace('COL', '', $fmt->formatCurrency($row->monto_cuota_uf, "COL"))) : $fmt->formatCurrency($row->monto_cuota_clp, "CLP");
            })
            ->addColumn('estado2', function ($row) {
                return '<td class="text-end"><span class="badge py-3 px-4 fs-7 badge-light-' . ($row->pagado == 1 ? 'primary' : ($row->estado == 'Por Vencer' ? 'success' : 'danger')) . '">' . ($row->pagado == 1 ? 'Pagado' : $row->estado) . '</span></td>';
            })
            ->addColumn('fechavencimiento', function ($row) {
                $orgDate = $row->fecha_vencimiento;
                $newDate = date("d/m/Y", strtotime($orgDate));
                return $newDate;
            })
            ->addColumn('fechapago', function ($row) {
                $orgDate = $row->fecha_pago;
                $newDate = $orgDate == null ? '-' : date("d/m/Y", strtotime($orgDate));
                return $newDate;
            })
            ->addColumn('mediopago', function ($row) {
                return $row->metodo == null ? '-' : $row->metodo;
            })
            ->rawColumns(['seleccionar', 'monto_cuota', 'estado2', 'fechavencimiento', 'fechapago', 'mediopago']);
        // ->rawColumns(['seleccionar', 'monto_cuota', 'estado2', 'fechavencimiento', 'fechapago', 'mediopago', 'acciones']);
        return $datable->toJson();
    }

    public function pagar(PagartransbankRequest $request)
    {
        $data = [];
        DB::transaction(function () use ($request, &$data) {
            if (!empty($request->lista)) {
                $monto = 0;
                // if ($request->moneda == 1) {
                $ufconversion = Ufconversion::orderBy('id', 'desc')->first();
                $pagodetalles = Pagodetalle::where('pagado', 0)->whereIn('id', $request->lista)->get();
                $detalle = [];
                $pagoid = '';
                /** @var Pagodetalle $pagodetalle */
                foreach ($pagodetalles as $pagodetalle) {
                    if (!isset($detalle[$pagodetalle->pago->proyecto->id])) {
                        $detalle[$pagodetalle->pago->proyecto->id] = [
                            'amount' => 0,
                            'commerce_code' => $pagodetalle->pago->proyecto->commerce_code,
                            'buy_order' => $pagodetalle->id,
                        ];
                    } else {
                        $pagos = explode('-', $detalle[$pagodetalle->pago->proyecto->id]['buy_order']);
                        $pagos[] = $pagodetalle->id;
                        $detalle[$pagodetalle->pago->proyecto->id]['buy_order'] = implode('-', $pagos);
                    }

                    $montoconversion = $ufconversion->monto_conversion * $pagodetalle->monto_cuota_uf;
                    $valorCuota = ($pagodetalle->idmoneda == 1 ? $montoconversion + ($montoconversion * 0.03) : $pagodetalle->monto_cuota_clp + ($pagodetalle->monto_cuota_clp * 0.03));
                    $monto += $valorCuota;
                    $detalle[$pagodetalle->pago->proyecto->id]['amount'] += (int)$valorCuota;
                    $pagoid = $pagoid . $pagodetalle->id . '-';
                }

                $pagoid = substr($pagoid, 0, -1);

                $pago = $pagodetalle->pago;

                $redirect = auth()->user() ? 'iniciocliente' : 'listadeudacliente';

                $response = (new WebpayPlus\MallTransaction)->create($pagoid, $pago->idcliente, route('transaccionbancosolorut', ['redirect' => $redirect]), array_values($detalle));

                $data = [
                    'token' => $response->getToken(),
                    'response' => json_decode(json_encode($response), true),
                    'request' => $request->except('_token'),
                    'monto' => $monto
                ];
            }
        });
        return response()->json($data);
    }

    public function transaccionbancosolorut(Request $request)
    {
        //Flujo normal
        if ($request->exists("token_ws")) {
            $req = $request->except('_token');
            $response = (new WebpayPlus\MallTransaction)->commit($req["token_ws"]);
            DB::beginTransaction();
            foreach ($response->details as $detail) {
                if ($detail->status) {
                    Transbank::create([
                        'status' => $detail->status,
                        'responsecode' => $detail->responseCode,
                        'authorizationcode' => $detail->authorizationCode,
                        'paymenttypecode' => $detail->paymentTypeCode,
                        'accountingdate' => $response->accountingDate,
                        'amount' => $detail->amount,
                        'installmentsnumber' => $detail->installmentsNumber,
                        'installmentsamount' => $detail->installmentsAmount,
                        'sessionid' => $response->sessionId,
                        'buyorder' => $detail->buyOrder,
                        'cardnumber' => $response->cardNumber,
                        // 'carddetail' => null,
                        'transactiondate' => $response->transactionDate,
                        //
                        'vci' => $response->vci
                    ]);

                    if ($detail->status == 'AUTHORIZED') {

                        $lista = str_replace('-', ',', $detail->buyOrder);
                        $lista2 = explode(',', $lista);

                        $ufconversion = Ufconversion::orderBy('id', 'desc')->first();
                        $pagodetalles = Pagodetalle::where('pagado', 0)->whereIn('id', $lista2)->get();
                        foreach ($pagodetalles as $pagodetalle) {

                            $pago = Pago::with('parcela')->where('id', $pagodetalle->idpago)->first();
                            $montoconversion = $ufconversion->monto_conversion * $pagodetalle->monto_cuota_uf;
                            $pagodetalle->update([
                                'idmetodopago' => 2,
                                'pagado' => 1,
                                'fecha_pago' => Carbon::now(),
                                'monto_cuota_pagado_uf' => ($pago->parcela->idmoneda == 1 ? $pagodetalle->monto_cuota_uf : null),
                                'monto_cuota_pagado_clp' => ($pago->parcela->idmoneda == 2 ? $pagodetalle->monto_cuota_clp : null),
                                'monto_restante_uf' => ($pago->parcela->idmoneda == 1 ? ($pago->monto_total_restante_uf - ($pagodetalle->monto_cuota_uf == null ? 0 : $pagodetalle->monto_cuota_uf)) : null),
                                'monto_restante_clp' => ($pago->parcela->idmoneda == 2 ? ($pago->monto_total_restante_clp - ($pagodetalle->monto_cuota_clp == null ? 0 : $pagodetalle->monto_cuota_clp)) : null),
                                'interes_pagado_uf' => ($pago->parcela->idmoneda == 1 ? ($pagodetalle->monto_cuota_uf * 0.03) : null),
                                'interes_pagado_clp' => ($pago->parcela->idmoneda == 2 ? ($pagodetalle->monto_cuota_clp * 0.03) : null),
                                'monto_cuota_pagado_uf_conversion_clp' => ($pago->parcela->idmoneda == 1 ? $montoconversion : null),
                                'interes_pagado_uf_conversion_clp' => ($pago->parcela->idmoneda == 1 ? ($montoconversion * 0.03) : null),
                                'fecha_pago_uf_conversion_clp' => ($pago->parcela->idmoneda == 1 ? $ufconversion->fecha_conversion : null),
                                'idclientepago' => $pago->idcliente,
                            ]);

                            $pagodetalleactualizado = Pagodetalle::where('id', $pagodetalle->id)->first();

                            Pago::where('id', $pagodetalleactualizado->idpago)->update([
                                'monto_total_restante_uf' => $pagodetalleactualizado->monto_restante_uf,
                                'monto_total_restante_clp' => $pagodetalleactualizado->monto_restante_clp,
                            ]);

                            $pagoactual = Pago::with('parcela')->where('id', $pagodetalleactualizado->idpago)->first();
                            $montorestante = $pagoactual->parcela->idmoneda == 1 ? $pagoactual->monto_total_restante_uf : $pagoactual->monto_total_restante_clp;
                            if ($montorestante <= 0) {
                                Parcela::where('id', $pago->idparcela)->update([
                                    'idestado' => 3
                                ]);
                            }
                        }
                    }
                }
            }
            DB::commit();
            $proyectos = [];
            foreach ($response->details as $detail) {
                $proyectos[$detail->commerceCode] = Proyecto::where('commerce_code', $detail->commerceCode)->first()->descripcion;
            }
            $redirect = $request->query->get('redirect') === 'iniciocliente' ? route('iniciocliente') : route('listadeudacliente', ['id' => Crypt::encrypt($response->sessionId)]);
            $request->session()->flash('tbk_data', ['token_ws' => $request->token_ws, 'response' => $response, 'proyectos' => $proyectos]);
            return redirect($redirect);
        }

        //Pago abortado
        if ($request->exists("TBK_TOKEN")) {
            //$request->session()->flash();
            return redirect()->back()->with('TBK_TOKEN', $request->TBK_TOKEN);
            // return 'abortado';
        }

        //Timeout
        // return view('webpayplus/transaction_timeout', ["resp" => $request->all()]);
        return redirect()->route('iniciocliente');
        // return 'Timeout';
    }
}
