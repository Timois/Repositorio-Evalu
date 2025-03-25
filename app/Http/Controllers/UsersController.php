<?php

namespace App\Http\Controllers;

use App\Models\Career;
use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function index()
    {
        $users = User::orderBy('id', 'asc')->get();
        $users->load('roles');
        return response()->json($users);
    }

    /**
     * Asignar un rol a un usuario.
     */

    public function findById($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function findAndUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            // 'ci' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|confirmed',
            'role' => 'required|array|min:1',
        ]);

        $user = User::findOrFail($id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json(['message' => 'Usuario actualizado exitosamente', 'user' => $user]);

    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|array|min:1',

        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'career_id' => null,
        ]);
        
        $user->assignRole($request->role);
        return response()->json(['message' => 'Usuario creado exitosamente', 'user' => $user]);
    }

    public function AssignCareer(Request $request)
    {
        
        $request->validate([
            'user_id' => 'required|exists:users,id', // Asegúrate de que sea 'personas' y no 'users'
            'career_id' => 'required|exists:careers,id',
        ]);

        // Obtener el usuario
        $user = User::findOrFail($request->user_id);
        
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
    
    public function deactivate($id)
    {
        $user = User::findOrFail($id);
        $user->status = 'inactivo';
        $user->save();
        return response()->json(['message' => 'Usuario dado de baja', 'user' => $user]);
    }
}
