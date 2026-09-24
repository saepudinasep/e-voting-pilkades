<?php

namespace App\Http\Middleware;

use App\Models\Tps;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeviceMatchesTps
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tokenable = $request->user();
        $kodeTpsDiUrl = $request->route('kodeTps');

        if (! $tokenable instanceof Tps || $tokenable->kode_tps !== $kodeTpsDiUrl) {
            abort(403, 'Token device ini tidak cocok dengan TPS yang diminta.');
        }

        return $next($request);
    }
}
