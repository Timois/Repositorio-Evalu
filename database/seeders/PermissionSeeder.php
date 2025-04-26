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
            'crear-unidades-academicas',
            'editar-unidades-academicas',
            'ver-carreras',
            'ver-unidades-academicas',
            'asignar-gestiones',
            'ver-gestiones-asignadas',
            'editar-carreras-asignadas',
            'ver-periodos-asignados',
            'asignar-periodos',
            'ver-unidades-por-id',
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
            'ver-postulantes',
            'crear-respuestas',
            'editar-respuestas',
            'ver-respuestas',
            'buscar-respuestas-porId',
            'dar-baja-respuestas',
            'ver-permisos-por-id',
            'asignar-carreras-a-usuarios',
            'ver-permisos-por-id',
            'ver-gestiones-asignadas-por-id',
            'editar-asignaciones',
            'ver-periodos-asignados-por-id',
            'ver-periodos-por-id',
            'ver-areas-por-id',
            'ver-preguntas-por-id',
            'ver-respuestas-por-id',
            'ver-respuestas-por-pregunta',
            'ver-periodos-asignados-por-carrera-y-gestion',
        ];

        // Crear permisos si no existen
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'persona']);
        }

        $this->command->info('✅ Permisos creados correctamente.');
    }
}

