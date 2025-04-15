<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationRoles;
use App\Models\Role;
use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        try {
            $request->validate([
                'name' => 'required|string|max:50|unique:roles,name,NULL,id,guard_name,persona',
                'permissions' => 'sometimes|array',
            ], [
                'name.unique' => 'Ya existe un rol con este nombre.',
                'name.required' => 'El nombre del rol es obligatorio.',
            ]);

            $role = Role::create([
                'name' => strtolower($request->name),
                'guard_name' => 'persona',
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            return response()->json([
                'success' => true,
                'data' => $role->load('permissions'),
                'message' => 'Rol creado exitosamente'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Error de validación'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el rol: ' . $e->getMessage()
            ], 500);
        }
    }

    public function findById(string $id)
    {
        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return response()->json(["message" => "El rol con id: $id no existe."], 404);
        }

        return response()->json($role);
    }


    public function update(Request $request, string $id)
    {
        try {
            $role = Role::findOrFail($id);

            // Validación básica primero
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:50',
                'permissions' => 'sometimes|array',
            ], [
                'name.required' => 'El nombre del rol es obligatorio.'
            ]);

            // Validación manual para nombre único considerando guard_name
            if ($request->name !== $role->name) {
                $existingRole = Role::where('name', strtolower($request->name))
                    ->where('guard_name', 'persona')
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingRole) {
                    return response()->json([
                        'success' => false,
                        'errors' => ['name' => ['Ya existe un rol con este nombre.']],
                        'message' => 'Error de validación'
                    ], 422);
                }
            }

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Error de validación'
                ], 422);
            }

            $role->update([
                'name' => strtolower($request->name),
                'guard_name' => 'persona'
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            return response()->json([
                'success' => true,
                'data' => $role->fresh()->load('permissions'),
                'message' => 'Rol actualizado exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el rol: ' . $e->getMessage()
            ], 500);
        }
    }

    public function remove($id)
    {
        try {
            $role = Role::findOrFail($id);
            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rol eliminado exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el rol: ' . $e->getMessage()
            ], 500);
        }
    }
    public function removePermission($role_id, $permission_id)
    {
        $role = Role::find($role_id);
        $role->revokePermissionTo($permission_id);
        return $role;
    }
}
