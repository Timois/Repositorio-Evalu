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

        $user->syncRoles($request->role);

        if ($request->filled('career_id')) {
            if ($user->hasAnyRole(['docente', 'director'])) {
                $career = Career::findOrFail($request->career_id);
                if ($career->type === 'carrera') {
                    $user->career_id = $career->id;
                    $user->save();
                } else {
                    return response()->json([
                        'errors' => [
                            'career_id' => ['Solo se pueden asignar carreras de tipo "carrera"']
                        ]
                    ], 422);
                }
            } else {
                return response()->json([
                    'errors' => [
                        'user_id' => ['Solo los usuarios con rol de docente o director pueden ser asignados a una carrera']
                    ]
                ], 422);
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

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'career_id' => null,
        ]);

        $user->syncRoles($request->role);

        if ($request->filled('career_id')) {
            if ($user->hasAnyRole(['docente', 'director'])) {
                $career = Career::findOrFail($request->career_id);
                if ($career->type === 'carrera') {
                    $user->career_id = $career->id;
                    $user->save();
                } else {
                    return response()->json([
                        'errors' => [
                            'career_id' => ['Solo se pueden asignar carreras de tipo "carrera"']
                        ]
                    ], 422);
                }
            } else {
                return response()->json([
                    'errors' => [
                        'user_id' => ['Solo los usuarios con rol de docente o director pueden ser asignados a una carrera']
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

        if (!$user->hasAnyRole(['docente', 'director'])) {
            return response()->json([
                'errors' => [
                    'user_id' => ['Solo los usuarios con rol de docente o director pueden ser asignados a una carrera']
                ]
            ], 422);
        }

        $career = Career::findOrFail($request->career_id);

        if ($career->type !== 'carrera') {
            return response()->json([
                'errors' => [
                    'career_id' => ['Solo se pueden asignar carreras de tipo "carrera"']
                ]
            ], 422);
        }

        $user->career_id = $request->career_id;
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
