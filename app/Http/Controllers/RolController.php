<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationRoles;
use App\Models\Role;
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
        $role = new Role();
        $role->name = strtolower($request->name);
        $role->guard_name = 'persona';
        $role->save();
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
        $role->delete();
        return $role;
    }

    public function removePermission($role_id, $permission_id)
    {
        $role = Role::find($role_id);
        $role->revokePermissionTo($permission_id);
        return $role;
    }
}
