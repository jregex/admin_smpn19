<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AksesGuru
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            // 2. Cek apakah user yang login memiliki role 'guru'
            if (auth()->user()->hasRole('guru')) {

                // Jika request halaman biasa, redirect ke dashboard dengan flash message
                return to_route('admin.dashboard')
                    ->with('error', 'Anda tidak memiliki hak akses untuk membuka halaman manajemen data tersebut.');
            }
        }

        return $next($request);
    }
}
