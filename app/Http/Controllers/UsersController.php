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
            'email' => 'required|email',
            'password' => 'required|string',
            'role' => 'required|array|min:1',
            'career_id' => 'nullable|exists:careers,id',
        ]);

        $user = User::findOrFail($id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->save();

        // Reemplazar los roles actuales con los nuevos
        $user->syncRoles($request->role);

        // Validar y asignar carrera si aplica
        if ($request->filled('career_id')) {
            if ($user->hasAnyRole(['docente', 'director'])) {
                $career = Career::findOrFail($request->career_id);
                if ($career->type === 'carrera') {
                    $user->career_id = $career->id;
                    $user->save();
                } else {
                    return response()->json(['message' => 'Solo se pueden asignar carreras de tipo "carrera"'], 403);
                }
            } else {
                return response()->json(['message' => 'Solo los usuarios con rol de docente o director pueden ser asignados a una carrera'], 403);
            }
        }

        return response()->json([
            'message' => 'Usuario actualizado exitosamente',
            'user' => $user,
            'roles' => $user->getRoleNames()
        ]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'role' => 'required|array|min:1',
            'career_id' => 'nullable|exists:careers,id',
        ]);

        // Crear el usuario sin asignar carrera aún
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'career_id' => null,
        ]);

        // Asignar roles
        $user->syncRoles($request->role);

        // Validar si se puede asignar carrera
        if ($request->filled('career_id')) {
            // Verifica si el usuario tiene rol docente o director
            if ($user->hasAnyRole(['docente', 'director'])) {
                $career = Career::findOrFail($request->career_id);
                if ($career->type === 'carrera') {
                    $user->career_id = $career->id;
                    $user->save();
                } else {
                    return response()->json(['message' => 'Solo se pueden asignar carreras de tipo "carrera"'], 403);
                }
            } else {
                return response()->json(['message' => 'Solo los usuarios con rol de docente o director pueden ser asignados a una carrera'], 403);
            }
        }

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'user' => $user,
            'roles' => $user->getRoleNames()
        ]);
    }


    public function AssignCareer(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'career_id' => 'required|exists:careers,id',
        ]);

        // Obtener el usuario
        $user = User::findOrFail($request->user_id);

        // Verificar con Spatie si tiene el rol adecuado
        if (!$user->hasAnyRole(['docente', 'director'])) {
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
