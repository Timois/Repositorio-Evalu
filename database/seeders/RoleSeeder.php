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
        $postulanteRole = Role::firstOrCreate(['name' => 'postulante', 'guard_name' => 'api']);
        // Asignar permisos a los roles
        $adminRole->syncPermissions(Permission::all()); 

        $superAdminRole->syncPermissions(Permission::all()); // 🔥 Super-Admin tiene todos los permisos
        $docenteRole->syncPermissions(['ver-unidades-por-id']);
        $postulanteRole->syncPermissions(['ver-resultados', 'ver-preguntas-asignadas'], 'ver-evaluaciones');
        $this->command->info('✅ Roles creados correctamente.');
    }
}
