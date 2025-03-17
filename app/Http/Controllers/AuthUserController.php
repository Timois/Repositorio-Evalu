<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\Providers\JWT;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthUserController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'role' => 'required|in:admin,docente,director,decano',

        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role,
            'career_id' => null,
        ]);

        return response()->json(['message' => 'Usuario creado exitosamente', 'user' => $user]);
    }

    public function login(Request $request)
    {
        // Validar datos de entrada
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        try {
            if(!$token = JWTAuth::attempt($credentials)){
                return response()->json(['error' => 'Credenciales incorrectas'], 401);
            }
            return response()->json(['token' => $token, 200]);
        }catch (JWTException $e) {
            return response()->json(['error' => 'Error al generar el token', 'error' => $e], 500);
        }
    }

    public function getUser() {
        $user = Auth::user();
        return response()->json($user, 200);
    }

    public function logout() {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Sesión cerrada correctamente'], 200);
    }

    public function me(){
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'error' => 'Token inválido o sesión cerrada'
            ], 401);
        }
        return response()->json($user, 200);
    }
}
