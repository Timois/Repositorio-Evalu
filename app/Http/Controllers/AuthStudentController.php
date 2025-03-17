<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthStudentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ci' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $persona = User::where('ci', $request->ci)->first();

        if (!$persona) {
            return response()->json(['error' => 'CI no encontrado'], 401);
        }
        
        if (!Hash::check($request->password, $persona->password)) {
            return response()->json(['error' => 'Contraseña incorrecta'], 401);
        }

         // Actualizar estado a activo
         $persona->update(['status' => 'activo']);
        
        // Generar token JWT
        $token = auth()->login($persona);

        // Retornar respuesta con el token
        return $this->respondWithToken($token);
    }

    public function me()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'error' => 'Token inválido o sesión cerrada'
            ], 401);
        }

        return response()->json($user);
    }

    public function logout()
    {
        auth()->logout(); // Mejor usar logout() en lugar de invalidate()
        
        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ]);
    }
}