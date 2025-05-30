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

        // Asignar permisos a admin y super-admin (guard persona)
        $allPersonaPermissions = Permission::where('guard_name', 'persona')->get();
        $adminRole->syncPermissions($allPersonaPermissions);
        $superAdminRole->syncPermissions($allPersonaPermissions);

        // Asignar permisos a docente (guard persona)
        $docentePermissions = Permission::where('name', 'ver-areas', 'ver-periodos')
            ->where('guard_name', 'persona')
            ->get();
        $docenteRole->syncPermissions($docentePermissions);

        // Asignar permisos a postulante (guard api)
        $postulantePermissions = Permission::whereIn('name', [
            'ver-resultados',
            'ver-preguntas-asignadas',
            'ver-evaluaciones',
            'ver-id-del-estudiante-por-ci',
        ])->where('guard_name', 'api')->get();
        $postulanteRole->syncPermissions($postulantePermissions);

        $this->command->info('✅ Roles y permisos asignados correctamente.');
    }
}
