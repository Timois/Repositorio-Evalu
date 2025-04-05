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
            'password' => 'nullable|string|min:6',
            'role' => 'required|array|min:1',
            'career_id' => 'nullable|exists:careers,id',
        ]);

        $roles = $request->role;
        $careerId = $request->career_id;

        // Validar que roles especiales no tengan carrera
        if ($careerId && collect($roles)->intersect(['admin', 'super-admin', 'decano'])->isNotEmpty()) {
            return response()->json([
                'errors' => [
                    'career_id' => ['Los usuarios con rol de admin, super-admin o decano no deben tener una carrera asignada.']
                ]
            ], 422);
        }

        $user = User::findOrFail($id);
        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        $user->syncRoles($roles);

        // Si se asignó carrera, verificar tipo
        if ($careerId) {
            $career = Career::findOrFail($careerId);
            if ($career->type === 'carrera') {
                $user->career_id = $career->id;
                $user->save();
            } else {
                return response()->json([
                    'errors' => [
                        'career_id' => ['Solo se pueden asignar carreras de tipo "carrera".']
                    ]
                ], 422);
            }
        } else {
            $user->career_id = null;
            $user->save();
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

        $roles = $request->role;
        $careerId = $request->career_id;

        // Si tiene rol admin, super-admin o decano, NO debe tener carrera asignada
        if ($careerId && collect($roles)->intersect(['admin', 'super-admin', 'decano'])->isNotEmpty()) {
            return response()->json([
                'errors' => [
                    'career_id' => ['Los usuarios con rol de admin, super-admin o decano no deben tener una carrera asignada.']
                ]
            ], 422);
        }

        // Crear usuario sin carrera inicialmente
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'career_id' => null,
        ]);

        $user->syncRoles($roles);

        // Si se asignó una carrera, verificar que sea de tipo "carrera"
        if ($careerId) {
            $career = Career::findOrFail($careerId);
            if ($career->type === 'carrera') {
                $user->career_id = $career->id;
                $user->save();
            } else {
                return response()->json([
                    'errors' => [
                        'career_id' => ['Solo se pueden asignar carreras de tipo "carrera".']
                    ]
                ], 422);
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

        $user = User::findOrFail($request->user_id);
        $career = Career::findOrFail($request->career_id);

        // Validar que el usuario NO tenga roles especiales
        if ($user->hasAnyRole(['admin', 'super-admin', 'decano'])) {
            return response()->json([
                'errors' => [
                    'user_id' => ['No se puede asignar una carrera a un usuario con rol de admin, super-admin o decano.']
                ]
            ], 422);
        }

        // Validar que la carrera sea del tipo correcto
        if ($career->type !== 'carrera') {
            return response()->json([
                'errors' => [
                    'career_id' => ['Solo se pueden asignar carreras de tipo "carrera".']
                ]
            ], 422);
        }

        $user->career_id = $career->id;
        $user->save();

        return response()->json([
            'message' => 'Carrera asignada exitosamente',
            'user' => $user
        ]);
    }



    public function deactivate($id)
    {
        $user = User::findOrFail($id);
        $user->status = 'inactivo';
        $user->save();
        return response()->json(['message' => 'Usuario dado de baja', 'user' => $user]);
    }
}
