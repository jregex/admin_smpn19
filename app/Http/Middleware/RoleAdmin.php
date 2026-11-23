<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            if (! auth()->user()->hasAnyRole(['superadmin', 'admin_sekolah'])) {
                return to_route('admin.dashboard')->with('error', 'Anda tidak memiliki hak akses untuk membuka halaman manajemen data tersebut.');
            }
        }

        return $next($request);
    }
}
