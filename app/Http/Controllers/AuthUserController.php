<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:persona', ['except' => ['login']]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = Auth::guard('persona')->attempt($credentials)) {

                return response()->json(['error' => 'Credenciales incorrectas'], 401);
            }

            $user = Auth::guard('persona')->user();

            // Obtener permisos agrupados por roles
            $rolesPermissions = $user->roles->mapWithKeys(function ($role) {
                return [$role->name => $role->permissions->pluck('name')];
            });

            return response()->json([
                'token' => $token,
                'user' => [
                    'nombre' => $user->name,
                    'email' => $user->email,
                    'career_id' => $user->career_id,
                    'role' => $user->roles->pluck('name'),
                ],
                'permissions' => $user->getAllPermissions()->pluck('name'), // todos los permisos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar el token',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function refreshPermissions()
    {
        $user = Auth::guard('persona')->user();

        $rolesPermissions = $user->roles->mapWithKeys(function ($role) {
            return [$role->name => $role->permissions->pluck('name')];
        });

        return response()->json([
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'roles_permissions' => $rolesPermissions,
        ]);
    }

    public function me()
    {
        $user = Auth::guard('persona')->user();

        if (!$user) {
            return response()->json(['error' => 'Token inválido o sesión cerrada'], 401);
        }

        // Cargar roles y sus permisos
        $user->load('roles.permissions');

        // Extraer los permisos de los roles
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions->pluck('name');
        })->unique()->values();

        return response()->json([
            'user' => $user,
            'permissions' => $permissions
        ]);
    }

    public function logout()
    {
        Auth::guard('persona')->logout();
        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    public function refresh()
    {
        return $this->respondWithToken(Auth::guard('persona')->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('persona')->factory()->getTTL() * 60,
        ]);
    }

    public function verifyToken(Request $request)
    {
        try {

            // Verificar si se proporciona un token
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Token no proporcionado',
                ], 400);
            }
            // Intentar autenticar con ambos guards
            $guards = ['persona', 'api'];

            foreach ($guards as $guard) {
                if ($user = Auth::guard($guard)->user()) {
                    return response()->json([
                        'valid' => true,
                        'message' => 'Token válido',
                        'guard' => $guard,
                        'user' => $user,
                    ], 200);
                }
            }

            return response()->json([
                'valid' => false,
                'message' => 'Token inválido o sesión cerrada',
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error al verificar el token',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
