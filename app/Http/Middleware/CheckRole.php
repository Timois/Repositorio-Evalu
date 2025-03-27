<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, $role)
    {
        if (!Auth::user() || !Auth::user()->hasRole($role)) {
            return response()->json(['message' => 'Acceso no autorizado'], Response::HTTP_FORBIDDEN);
        }
        return $next($request);
    }
}

