<?php

namespace App\Http\Controllers;

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
        $validated = $request->validate([
            'name' => 'required|string|max:20',
            'permissions' => 'required|array',
        ]);
        $role = new Role();
        $role->name = $validated['name'];
        $role->guard_name = $validated['name'];
        $role->save();
        $role->syncPermissions($validated['permissions']);
        return $role;
    }


    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:20',
            'permissions' => 'required|array',
        ]);

        $role = Role::find($id);

        $role->name = $validated['name'];
        $role->save();
        $role->syncPermissions($validated['permissions']);
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
