<?php

namespace App\Http\Middleware;

use App\Models\Pago;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UsuarioConsolidadoMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $pago = Pago::where('idcliente', auth()->user()->id)->first();
        if(auth()->user()->administrador == 0 && !empty($pago)){
            return $next($request);
        }
        return redirect()->route('inicio');
    }
}
