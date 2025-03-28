<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationRoles;
use App\Models\Role;
use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function index()
    {
        $roles = Role::orderBy('id', 'asc')->get();
        $roles->load('permissions');
        return response()->json($roles);
    }

    public function create(Request $request)
    {
        $validate = $request->validate([
            'name' => 'required',
        ]);

        $role = Role::create([
            'name' => strtolower($request->name),
            'guard_name' => 'persona',
        ]);
        $role->syncPermissions($request->permissions);
        return $role;
    }



    public function update(ValidationRoles $request, string $id)
    {
        $role = Role::find($id);
        if ($role) {
            $role->name = strtolower($request->name);
            $role->guard_name = 'persona';
            $role->save();
            $role->syncPermissions($request->permissions);
        }
        return $role;
    }

    public function remove($id)
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }
        $role->delete();
        return response()->json(['message' => 'Rol eliminado exitosamente']);
    }

    public function removePermission($role_id, $permission_id)
    {
        $role = Role::find($role_id);
        $role->revokePermissionTo($permission_id);
        return $role;
    }
}
