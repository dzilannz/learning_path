<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $role = null)
    {
        // Log awal middleware
        Log::info('AuthMiddleware: Start', [
            'url' => $request->url(),
            'session_data' => $request->session()->all(),
        ]);
    
        // Izinkan akses langsung ke halaman landing page tanpa pengecekan
        if ($request->is('/') || $request->is('landing') || $request->is('landing/*')) {
            Log::info('AuthMiddleware: Allowing access to landing page or filter.');
            return $next($request);
        }
        

    
        if ($request->is('login') || $request->is('login/*')) {
            return $next($request);
        }

        
    
        if (!$request->hasSession()) {
            Log::error('Session not initialized!');
            abort(500, 'Session not initialized.');
        }
    
        $user = $request->session()->get('user');
        
        // Log user session
        Log::info('AuthMiddleware: User Session', ['user' => $user]);
    
        if (!$user) {
            Log::warning('No user in session, redirecting to login.');
            return redirect()->route('login.form');
        }
    
        if ($role && (!isset($user['role']) || $user['role'] !== $role)) {
            Log::warning('Role mismatch or unauthorized access.', [
                'required_role' => $role,
                'user_role' => $user['role'] ?? null,
            ]);
            return abort(403, 'Unauthorized access');
        }
    
        // Log successful middleware pass
        Log::info('AuthMiddleware: Passed validation.');
    
        return $next($request);
    }
    
}
