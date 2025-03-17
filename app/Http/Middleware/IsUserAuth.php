<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class IsUserAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user || !$user instanceof User) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Si se pasan roles al middleware, verifica si el usuario tiene uno de ellos
            if (!empty($roles) && !in_array($user->role, $roles)) {
                return response()->json(['error' => 'Acceso denegado para tu rol'], 403);
            }

        } catch (JWTException $e) {
            return response()->json(['error' => 'Token inválido o expirado', $e], 401);
        }

        return $next($request);
    }
}
