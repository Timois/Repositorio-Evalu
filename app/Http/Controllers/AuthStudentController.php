<?php

namespace App\Http\Controllers;

use App\Models\UserStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AuthStudentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['loginStudent']]);
    }

    public function loginStudent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ci' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $persona = UserStudent::where('ci', $request->ci)->first();

        if (!$persona) {
            return response()->json(['error' => 'CI no encontrado'], 401);
        }
        
        if (!Hash::check($request->password, $persona->password)) {
            return response()->json(['error' => 'Contraseña incorrecta'], 401);
        }

        // Actualizar estado a activo
        $persona->update(['status' => 'activo']);
        
        // Generar token JWT con el guard correcto
        $token = Auth::guard('api')->login($persona);

        return $this->respondWithToken($token);
    }

    public function me()
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'error' => 'Token inválido o sesión cerrada'
            ], 401);
        }

        return response()->json($user);
    }

    public function logoutStudent()
    {
        Auth::guard('api')->logout();
        
        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    public function refresh()
    {
        return $this->respondWithToken(Auth::guard('api')->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ]);
    }
}
