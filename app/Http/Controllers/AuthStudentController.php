<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Role;
use App\Models\UserStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        // Buscar al usuario por CI
        $persona = UserStudent::where('ci', $request->ci)->first();

        $grupo_student = DB::table('group_student')->where('student_id', $persona->id)->first();

        $grupo = Group::find($grupo_student->group_id);

        if (!$persona) {
            return response()->json(['error' => 'CI no encontrado'], 401);
        }

        // Verificar la contraseña
        if (!Hash::check($request->password, $persona->password)) {
            return response()->json(['error' => 'Contraseña incorrecta'], 401);
        }

        // ✅ Si no ha iniciado aún, ponerlo como "activo" y continuar
        $persona->update(['status' => 'activo']);
        $rol = Role::where('name', 'postulante')->first();

        $token = Auth::guard('api')->login($persona);

        return response()->json([
            'token' => $token,
            'user' => [
                'nombre_completo' => trim(
                    $persona->name . ' ' .
                        ($persona->paternal_surname ?? '') . ' ' .
                        ($persona->maternal_surname ?? '')
                ),
                'ci' => $persona->ci,
                'group' => $grupo->id,
                'role' => $rol->name,
            ],
            'permissions' => $rol->permissions->pluck('name'),

        ]);
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

    public function verifyStudentToken(Request $request)
    {
        $user = Auth::guard('api')->user();

        return response()->json([
            'valid' => !!$user,
            'role'  => 'student',
            'user'  => $user,
        ]);
    }
}
