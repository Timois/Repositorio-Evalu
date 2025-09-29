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
            'editar-periodos-asignados',
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
            'ver-periodos-por-carrera',
            'ver-areas-por-id',
            'ver-preguntas-por-id',
            'ver-respuestas-por-id',
            'ver-respuestas-por-pregunta',
            'ver-periodos-asignados-por-carrera-y-gestion',
            'ver-informacion-del-periodo-asignado',
            'generar-pruebas-aleatorias',
            'asignar-cantidad-preguntas',
            'ver-preguntas-disponibles',
            'ver-preguntas-asignadas',
            'ver-postulantes-por-evaluacion',
            'ver-id-del-estudiante-por-ci',
            'registrar-postulantes',
            'ver-postulantes-por-periodo',
            'crear-grupos',
            'editar-grupos',
            'ver-grupos',
            'ver-grupos-por-id',
            'ver-grupos-por-evaluacion',
            'crear-laboratorios',
            'editar-laboratorios',
            'ver-laboratorios', 
            'ver-laboratorios-por-id',
            'ver-evaluaciones-por-periodo',
            'ver-evaluaciones-por-carrera',
            'ver-periodos-asignados-por-carrera',
            'dar-baja-areas',
            'ver-cantidad-de-preguntas-por-area',
            'ver-areas-activas-por-carrera',
            'eliminar-importacion',
            'ver-areas-por-excel',
            'ver-resultados-por-evaluacion',
            'iniciar-evaluacion-grupo',
            'pausar-evaluacion-grupo',
            'reanudar-evaluacion-grupo',
            'finalizar-evaluacion-grupo',
            'ver-resultados-por-grupo',
            'activar-preguntas',
        ];

        // Crear permisos si no existen
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'persona']);
        }
        $permissionsStudent = [
            'ver-resultados',
            'ver-preguntas-asignadas',
            'ver-evaluaciones',
            'ver-id-del-estudiante-por-ci',
        ];
        // Crear permisos para postulantes
        foreach ($permissionsStudent as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }
        $this->command->info('✅ Permisos creados correctamente.');
    }
}
