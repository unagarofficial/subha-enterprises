<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session('user_type') !== 'ADMIN') {
            return redirect()->route('dashboard')->with('error', 'Access denied. Admin only.');
        }

        return $next($request);
    }
}
