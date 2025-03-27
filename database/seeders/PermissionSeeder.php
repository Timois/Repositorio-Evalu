<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Eliminar caché de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Lista de permisos
        $permissions = [
            'crear-usuarios',
            'editar-usuarios',
            'ver-usuarios',
            'eliminar-usuarios',
            'crear-roles',
            'editar-roles',
            'ver-roles',
            'crear-permisos',
            'editar-permisos',
            'ver-permisos'
        ];

        // Crear permisos si no existen
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'persona']);
        }

        $this->command->info('✅ Permisos creados correctamente.');
    }
}

