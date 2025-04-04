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
            return response()->json(['token' => $token], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar el token',
                'details' => $e->getMessage(), // Mostrar detalles del error
            ], 500);
        }
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

    public function generateToken(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = Auth::guard('persona')->attempt($credentials)) {
            return response()->json(['error' => 'Credenciales incorrectas'], 401);
        }

        $user = Auth::guard('persona')->user();

        $customClaims = [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
        ];
        
        $tokenWithClaims = Auth::guard('persona')->claims($customClaims)->attempt($credentials);

        return $this->respondWithToken($tokenWithClaims);
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
}
