<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsPetugasOrAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, ['admin', 'petugas_tps'], true)) {
            abort(403, 'Halaman ini khusus petugas TPS atau admin.');
        }

        if ($user->role === 'petugas_tps' && ! $user->tps_id) {
            abort(403, 'Akun petugas ini belum ditempatkan di TPS mana pun. Hubungi admin.');
        }

        return $next($request);
    }
}
