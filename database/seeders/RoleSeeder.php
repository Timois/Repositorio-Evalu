<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Eliminar caché de roles
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'persona']);
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'persona']);
        $docenteRole = Role::firstOrCreate(['name' => 'docente', 'guard_name' => 'persona']);

        // Asignar permisos a los roles
        $adminRole->syncPermissions([
            'crear-usuarios', 'editar-usuarios', 'ver-usuarios', 'eliminar-usuarios',
            'crear-roles', 'editar-roles', 'ver-roles'
        ]);

        $superAdminRole->syncPermissions(Permission::all()); // 🔥 Super-Admin tiene todos los permisos
        $docenteRole->syncPermissions(['ver-usuarios']);
        $this->command->info('✅ Roles creados correctamente.');
    }
}
