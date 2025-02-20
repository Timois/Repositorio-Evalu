<?php

namespace App\Http\Controllers;

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
    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:admin,docente,director,decano',
        ]);

        $user = User::findOrFail($id);
        $user->role = $request->role;
        $user->save();

        return response()->json(['message' => 'Rol actualizado exitosamente', 'user' => $user]);
    }
}
