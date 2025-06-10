<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationGroup;
use App\Models\Group;
use App\Models\Laboratorie;
use App\Models\StudentTest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupsController extends Controller
{
    public function find()
    {
        $groups = Group::orderBy('id', 'asc')->get();
        return response()->json($groups);
    }

    public function findById(string $id)
    {
        $group = Group::find($id);
        if ($group) {
            return response()->json($group);
        } else {
            return response()->json(['message' => 'No se encontro el grupo'], 404);
        }
    }

    // Lista los grupos de una evaluación específica
    public function findGroupsByEvaluation(string $evaluationId)
    {
        $groups = Group::with('students')
            ->where('evaluation_id', $evaluationId)
            ->get();

        return response()->json($groups);
    }


    public function create(ValidationGroup $request)
    {
        // 1. Obtener los estudiantes
        $studentsQuery = StudentTest::with('student')
            ->where('evaluation_id', $request->evaluation_id);

        if ($request->order_type === 'alphabetical') {
            $studentsQuery->join('students', 'student_tests.student_id', '=', 'students.id')
                ->orderBy('students.paternal_surname', 'asc');
        } elseif ($request->order_type === 'id_asc') {
            $studentsQuery->join('students', 'student_tests.student_id', '=', 'students.id')
                ->orderBy('students.id', 'asc');
        } else {
            return response()->json(['message' => 'Tipo de orden no válido. Use "alphabetical" o "id_asc".'], 400);
        }

        $students = $studentsQuery->get();
        $totalStudents = $students->count();

        if ($totalStudents === 0) {
            return response()->json(['message' => 'No hay estudiantes en esta evaluación.'], 400);
        }

        // 2. Obtener la capacidad del laboratorio seleccionado
        $laboratory = Laboratorie::find($request->laboratory_id);
        if (!$laboratory) {
            return response()->json(['message' => 'Laboratorio no encontrado.'], 404);
        }
        $laboratoryCapacity = $laboratory->equipment_count;

        // 3. Validar que el laboratorio tenga suficiente capacidad
        if ($laboratoryCapacity <= 5) {
            return response()->json(['message' => 'El laboratorio debe tener más de 3 equipos para permitir al menos 3 equipos libres.'], 400);
        }

        // 4. Verificar si hay suficientes estudiantes sin asignar
        $assignedStudentsCount = DB::table('group_student')
            ->whereIn('student_id', $students->pluck('student_id'))
            ->distinct('student_id')
            ->count();

        $unassignedStudentsCount = $totalStudents - $assignedStudentsCount;

        if ($unassignedStudentsCount === 0) {
            return response()->json(['message' => 'No hay estudiantes sin asignar para este grupo.'], 400);
        }

        // 5. Calcular cuántos estudiantes asignar al grupo de manera equitativa
        $maxStudentsPerGroup = $laboratoryCapacity - 3; // Capacidad ajustada
        // Estimar cuántos grupos son necesarios para los estudiantes sin asignar
        $estimatedGroupsNeeded = ceil($unassignedStudentsCount / $maxStudentsPerGroup);
        // Calcular estudiantes por grupo para una distribución equitativa
        $studentsToAssignCount = ceil($unassignedStudentsCount / max(1, $estimatedGroupsNeeded));

        // Asegurarse de no exceder la capacidad ajustada
        $studentsToAssignCount = min($studentsToAssignCount, $maxStudentsPerGroup);

        // 6. Crear y guardar el grupo
        $group = new Group();
        $group->evaluation_id = $request->evaluation_id;
        $group->laboratory_id = $request->laboratory_id;
        $group->name = $request->name;
        $group->description = $request->description;
        $group->start_time = Carbon::parse($request->start_time);
        $group->end_time = Carbon::parse($request->end_time);
        $group->save();

        // 7. Asignar estudiantes al grupo
        // Obtener los IDs de estudiantes ya asignados
        $assignedStudentIds = DB::table('group_student')
            ->select('student_id')
            ->pluck('student_id')
            ->toArray();

        // Filtrar estudiantes no asignados y tomar la cantidad calculada
        $studentsToAssign = $students->whereNotIn('student_id', $assignedStudentIds)
            ->take($studentsToAssignCount);

        $assignments = [];
        foreach ($studentsToAssign as $student) {
            $assignments[] = [
                'group_id' => $group->id,
                'student_id' => $student->student_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insertar las asignaciones en la tabla group_student
        if (!empty($assignments)) {
            DB::table('group_student')->insert($assignments);
        }

        // 8. Calcular estudiantes sin asignar después de la asignación
        $newAssignedCount = count($assignments);
        $newUnassignedStudentsCount = $unassignedStudentsCount - $newAssignedCount;

        // 9. Respuesta con información del grupo y estudiantes sin asignar
        return response()->json([
            'group' => $group,
            'assigned_students' => $newAssignedCount,
            'unassigned_students' => $newUnassignedStudentsCount,
            'available_equipment' => $laboratoryCapacity - $newAssignedCount,
            'message' => 'Grupo creado y estudiantes asignados equitativamente, dejando al menos equipos libres.'
        ], 201);
    }

    public function update(ValidationGroup $request, string $id)
    {
        // 1. Buscar el grupo
        $group = Group::find($id);
        if (!$group) {
            return response()->json(['message' => 'Grupo no encontrado.'], 404);
        }

        // 2. Validar el laboratorio si se proporciona un nuevo laboratory_id
        $laboratoryId = $request->has('laboratory_id') ? $request->laboratory_id : $group->laboratory_id;
        $laboratory = Laboratorie::find($laboratoryId);
        if (!$laboratory) {
            return response()->json(['message' => 'Laboratorio no encontrado.'], 404);
        }
        $laboratoryCapacity = $laboratory->equipment_count;

        // 3. Validar que el laboratorio tenga suficiente capacidad
        if ($laboratoryCapacity <= 5) {
            return response()->json(['message' => 'El laboratorio debe tener más de 5 equipos para permitir al menos 3 equipos libres.'], 400);
        }

        // 4. Obtener los estudiantes de la evaluación
        $evaluationId = $request->has('evaluation_id') ? $request->evaluation_id : $group->evaluation_id;
        $studentsQuery = StudentTest::with('student')
            ->where('evaluation_id', $evaluationId);

        // 5. Ordenar estudiantes según el tipo de orden
        $orderType = $request->has('order_type') ? $request->order_type : 'alphabetical'; // Valor por defecto
        if ($orderType === 'alphabetical') {
            $studentsQuery->join('students', 'student_tests.student_id', '=', 'students.id')
                ->orderBy('students.paternal_surname', 'asc');
        } elseif ($orderType === 'id_asc') {
            $studentsQuery->join('students', 'student_tests.student_id', '=', 'students.id')
                ->orderBy('students.id', 'asc');
        } else {
            return response()->json(['message' => 'Tipo de orden no válido. Use "alphabetical" o "id_asc".'], 400);
        }

        $students = $studentsQuery->get();
        $totalStudents = $students->count();

        if ($totalStudents === 0) {
            return response()->json(['message' => 'No hay estudiantes en esta evaluación.'], 400);
        }

        // 6. Verificar estudiantes sin asignar
        $assignedStudentsCount = DB::table('group_student')
            ->whereIn('student_id', $students->pluck('student_id'))
            ->distinct('student_id')
            ->count();

        $unassignedStudentsCount = $totalStudents - $assignedStudentsCount;

        if ($unassignedStudentsCount === 0 && $request->has('reassign_students')) {
            return response()->json(['message' => 'No hay estudiantes sin asignar para este grupo.'], 400);
        }

        // 7. Calcular cuántos estudiantes asignar al grupo de manera equitativa
        $maxStudentsPerGroup = $laboratoryCapacity - 5; // Capacidad ajustada
        $estimatedGroupsNeeded = ceil($unassignedStudentsCount / $maxStudentsPerGroup);
        $studentsToAssignCount = ceil($unassignedStudentsCount / max(1, $estimatedGroupsNeeded));
        $studentsToAssignCount = min($studentsToAssignCount, $maxStudentsPerGroup);

        // 8. Actualizar los campos del grupo
        if ($request->has('evaluation_id')) {
            $group->evaluation_id = $evaluationId;
        }
        if ($request->has('laboratory_id')) {
            $group->laboratory_id = $laboratoryId;
        }
        if ($request->has('name')) {
            $group->name = $request->name;
        }
        if ($request->has('description')) {
            $group->description = $request->description;
        }
        if ($request->has('start_time')) {
            $group->start_time = Carbon::parse($request->start_time);
        }
        if ($request->has('end_time')) {
            $group->end_time = Carbon::parse($request->end_time);
        }
        $group->save();

        // 9. Reasignar estudiantes si se solicita
        $newAssignedCount = 0;
        if ($request->has('reassign_students') && $request->reassign_students) {
            // Eliminar asignaciones previas del grupo
            DB::table('group_student')->where('group_id', $group->id)->delete();

            // Obtener los IDs de estudiantes ya asignados a otros grupos
            $assignedStudentIds = DB::table('group_student')
                ->select('student_id')
                ->pluck('student_id')
                ->toArray();

            // Filtrar estudiantes no asignados y tomar la cantidad calculada
            $studentsToAssign = $students->whereNotIn('student_id', $assignedStudentIds)
                ->take($studentsToAssignCount);

            $assignments = [];
            foreach ($studentsToAssign as $student) {
                $assignments[] = [
                    'group_id' => $group->id,
                    'student_id' => $student->student_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insertar las nuevas asignaciones
            if (!empty($assignments)) {
                DB::table('group_student')->insert($assignments);
                $newAssignedCount = count($assignments);
            }
        } else {
            // Contar estudiantes actualmente asignados al grupo
            $newAssignedCount = DB::table('group_student')
                ->where('group_id', $group->id)
                ->count();
        }

        // 10. Calcular estudiantes sin asignar después de la asignación
        $newUnassignedStudentsCount = $unassignedStudentsCount - $newAssignedCount;

        // 11. Respuesta con información actualizada
        return response()->json([
            'group' => $group,
            'assigned_students' => $newAssignedCount,
            'unassigned_students' => $newUnassignedStudentsCount,
            'available_equipment' => $laboratoryCapacity - $newAssignedCount,
            'message' => 'Grupo actualizado' . ($request->reassign_students ? ' y estudiantes reasignados equitativamente.' : '.')
        ], 200);
    }
}
