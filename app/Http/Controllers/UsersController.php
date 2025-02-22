<?php

namespace App\Http\Controllers;

use App\Models\Career;
use App\Models\Persona;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function index()
    {
        $users = Persona::all();
        return response()->json($users);
    }

    /**
     * Asignar un rol a un usuario.
     */

    public function findById($id)
    {
        $user = Persona::findOrFail($id);
        return response()->json($user);
    }

    public function findrols($roleName)
    {
        // Buscar usuarios con el tipo de rol especificado
        $users = Persona::where('role', $roleName)->get();

        return response()->json($users);
    }

    public function findAndUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            // 'ci' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'role' => 'required|in:admin,docente,director,decano',
        ]);

        $user = Persona::findOrFail($id);
        $user->name = $request->name;
        // $user->ci = $request->ci;
        $user->email = $request->email;
        $user->password = $request->password;
        $user->role = $request->role;
        $user->save();

        return response()->json(['message' => 'Usuario actualizado exitosamente', 'user' => $user]);
    }

    public function assignRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:personas,id',
            'role' => 'required|in:admin,docente,director,decano',
        ]);

        $user = Persona::findOrFail($request->user_id);
        $user->role = $request->role;
        $user->save();

        return response()->json(['message' => 'Rol asignado exitosamente', 'user' => $user]);
    }
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
            'role' => 'required|in:admin,docente,director,decano',

        ]);

        $user = Persona::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role,
            'career_id' => null,
        ]);

        return response()->json(['message' => 'Usuario creado exitosamente', 'user' => $user]);
    }

    public function AssignCareer(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:personas,id', // Asegúrate de que sea 'personas' y no 'users'
            'career_id' => 'required|exists:careers,id',
        ]);

        // Obtener el usuario
        $user = Persona::findOrFail($request->user_id);

        // Verificar que el usuario tenga el rol de docente o director
        if (!in_array($user->role, ['docente', 'director'])) {
            return response()->json(['message' => 'Solo los usuarios con rol de docente o director pueden ser asignados a una carrera'], 403);
        }

        // Obtener la carrera
        $career = Career::findOrFail($request->career_id);

        // Verificar que la carrera sea de tipo "carrera"
        if ($career->type !== 'carrera') {
            return response()->json(['message' => 'Solo se pueden asignar carreras de tipo "carrera"'], 403);
        }

        // Asignar la carrera al usuario
        $user->career_id = $request->career_id;
        $user->save();

        return response()->json(['message' => 'Carrera asignada exitosamente', 'user' => $user]);
    }
    public function assignDecano(Request $request) {

        $request->validate([
            'user_id' => 'required|exists:personas,id',
            'career_id' => 'required|exists:careers,id',
        ]);

        // Obtener el usuario
        $user = Persona::findOrFail($request->user_id);

        // Verificar que el usuario tenga el rol de decano
        if ($user->role !== 'decano') {
            return response()->json(['message' => 'Solo los usuarios con rol de decano pueden ser asignados a una carrera'], 403);
        }

        // Verificar que la carrera sea de tipo "facultad"
        $career = Career::findOrFail($request->career_id);
        if ($career->type !== 'facultad') {
            return response()->json(['message' => 'Solo se pueden asignar carreras de tipo "facultad"'], 403);
        }

        // Asignar la unidad al usuario
        $user->career_id = $request->career_id;
        $user->save();

        return response()->json(['message' => 'Unidad asignada exitosamente', 'user' => $user]);
    }
}
