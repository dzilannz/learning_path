<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        Log::info('CustomAuthMiddleware: Start', [
            'url' => $request->url(),
            'session_data' => $request->session()->all(),
        ]);

        // Untuk custom-login, lanjutkan tanpa pengecekan sesi
        if ($request->is('custom-login')) {
            Log::info('CustomAuthMiddleware: Allowing access to custom-login.');
            return $next($request);
        }

        // Tambahkan logika lain jika diperlukan

        return $next($request);
    }
}
