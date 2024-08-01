<?php

namespace App\Http\Middleware;

use App\Models\Ufconversion;
use App\Services\UFConversionService;
use Carbon\Carbon;
use Closure;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConversionMiddleware
{
    private $UFService;
    public function __construct(UFConversionService $UFService)
    {
        $this->UFService = $UFService;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $hoy = Carbon::now();
        $this->UFService->getUFConversion($hoy);
        return $next($request);
    }
}
