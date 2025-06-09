<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationGroup;
use App\Models\Group;
use App\Models\Laboratorie;
use App\Models\StudentTest;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        $groups = Group::where('evaluation_id', $evaluationId)->get();
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
        } elseif ($request->order_type === 'random') {
            $studentsQuery->inRandomOrder();
        }

        $students = $studentsQuery->get();

        $totalStudents = $students->count();

        if ($totalStudents === 0) {
            return response()->json(['message' => 'No hay estudiantes en esta evaluación.'], 400);
        }

        // 2. Obtener laboratorios
        $laboratories = \App\Models\Laboratorie::whereIn('id', $request->laboratory_ids)->get();

        if ($laboratories->isEmpty()) {
            return response()->json(['message' => 'No se encontraron los laboratorios seleccionados.'], 400);
        }

        // 3. Obtener la fecha del examen
        $evaluation = \App\Models\Evaluation::find($request->evaluation_id);
        $examDate = \Carbon\Carbon::parse($evaluation->date_of_realization)->format('Y-m-d');

        // 4. Preparar horarios base
        $startTimeBase = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $examDate . ' ' . $request->start_time);
        $endTimeBase = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $examDate . ' ' . $request->end_time);

        // 5. Asignar estudiantes en grupos por turnos
        $currentIndex = 0;
        $groupCounter = 1;
        $groupedStudents = [];

        while ($currentIndex < $totalStudents) {
            foreach ($laboratories as $lab) {
                if ($currentIndex >= $totalStudents) break;

                $equipmentsAvailable = $lab->equipment_count;
                $studentsPerGroup = min($equipmentsAvailable, $totalStudents - $currentIndex);

                // Crear grupo
                $group = new Group();
                $group->evaluation_id = $request->evaluation_id;
                $group->name = $request->name . ' (' . $lab->name . ') - Turno ' . $groupCounter;
                $group->description = $request->description . ' (Grupo ' . $groupCounter . ' - ' . $lab->name . ')';
                $group->total_students = $studentsPerGroup;
                $group->laboratory_id = $lab->id;
                $group->start_time = $startTimeBase->copy();
                $group->end_time = $endTimeBase->copy();
                $group->save();

                // Asignar estudiantes al grupo
                $studentIds = [];
                for ($j = 0; $j < $studentsPerGroup && $currentIndex < $totalStudents; $j++, $currentIndex++) {
                    $studentTest = $students[$currentIndex];
                    $studentIds[] = $studentTest->student_id;
                }

                $group->students()->attach($studentIds);

                $groupedStudents[] = [
                    'group_id' => $group->id,
                    'students' => $studentIds,
                    'laboratory' => $lab->name,
                    'turno' => $groupCounter,
                    'start_time' => $group->start_time->format('Y-m-d H:i'),
                    'end_time' => $group->end_time->format('Y-m-d H:i')
                ];

                $groupCounter++;

                // Avanzar horario base para el siguiente turno
                $durationMinutes = $startTimeBase->diffInMinutes($endTimeBase);
                $startTimeBase->addMinutes($durationMinutes);
                $endTimeBase->addMinutes($durationMinutes);
            }
        }

        return response()->json([
            'total_groups_created' => $groupCounter - 1,
            'total_students' => $totalStudents,
            'groups' => $groupedStudents
        ], 201);
    }


    public function update(ValidationGroup $request, string $id)
    {
        $group = Group::find($id);
        if (!$group) {
            return response()->json(['message' => 'No se encontro el grupo'], 404);
        }
        if ($request->has('evaluation_id')) {
            $group->evaluation_id = $request->evaluation_id;
        }

        if ($request->has('name')) {
            $group->name = $request->name;
        }

        if ($request->has('description')) {
            $group->description = $request->description;
        }

        if ($request->has('start_time')) {
            $group->start_time = $request->start_time;
        }

        if ($request->has('end_time')) {
            $group->end_time = $request->end_time;
        }

        // Recalcular total_students si cambió evaluation_id
        if ($request->has('evaluation_id')) {
            $totalStudents = StudentTest::where('evaluation_id', $request->evaluation_id)->count();
            $group->total_students = $totalStudents;
        }

        $group->save();
        return response()->json($group, 200);
    }
}
