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
            'ver-permisos',
            'crear-carreras',
            'editar-carreras',
            'ver-carreras',
            'asignar-gestiones',
            'ver-gestiones-asignadas',
            'editar-carreras-asignadas',
            'ver-periodos-asignados',
            'asignar-periodos',
            'crear-unidades',
            'editar-unidades',
            'ver-unidades',
            'crear-periodos',
            'editar-periodos',
            'ver-periodos',
            'crear-gestiones',
            'editar-gestiones',
            'ver-gestiones',
            'crear-areas',
            'editar-areas',
            'ver-areas',
            'ver-preguntas-por-area',
            'importar-excel',
            'ver-importaciones',
            'editar-importaciones',
            'importar-excel-con-imagenes',
            'ver-importaciones-con-imagenes',
            'editar-importaciones-con-imagenes',
            'crear-preguntas',
            'editar-preguntas',
            'ver-preguntas',
            'buscar-preguntas-porId',
            'dar-baja-preguntas',
            'crear-evaluaciones',
            'editar-evaluaciones',
            'ver-evaluaciones',
            'buscar-evaluaciones-porId',
            'ver-preguntas-asignadas',
            'crear-preguntas-evaluaciones',
            'editar-preguntas-evaluaciones',
            'asignar-preguntas-evaluaciones',
            'ver-preguntas-asignadas',
            'asignar-puntajes-evaluaciones',
            'ver-puntajes-asignados',
            'importar-postulantes',
            'ver-importaciones-de-postulantes',
            'editar-importaciones-de-postulantes',
            'buscar-importaciones-de-postulantes-porId',
            'crear-respuestas',
            'editar-respuestas',
            'ver-respuestas',
            'buscar-respuestas-porId',
            'dar-baja-respuestas',
        ];

        // Crear permisos si no existen
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'persona']);
        }

        $this->command->info('✅ Permisos creados correctamente.');
    }
}

