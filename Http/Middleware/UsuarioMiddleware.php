<?php

namespace App\Http\Middleware;

use App\Models\Ufconversion;
use Carbon\Carbon;
use Closure;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UsuarioMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->user()->administrador == 0) {
            return $next($request);
        }
        return redirect()->route('logincliente');
    }
}
