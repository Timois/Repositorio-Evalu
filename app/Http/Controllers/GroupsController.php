<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationGroup;
use App\Models\Evaluation;
use App\Models\Group;
use App\Models\Laboratorie;
use App\Models\Result;
use App\Models\Student;
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
        $groups = Group::with(['lab', 'students'])
            ->where('evaluation_id', $evaluationId)
            ->orderBy('id', 'asc')
            ->get();

        $studentsCount = StudentTest::where('evaluation_id', $evaluationId)
            ->count();

        return response()->json([
            'groups' => $groups,
            'total_students' => $studentsCount,
        ]);
    }

    public function getGroupStatus($groupId)
    {
        $group = Group::with('evaluation')->find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Grupo no encontrado'], 404);
        }

        $now = now();

        // 🔥 Calcular time_left dependiendo del estado
        if ($group->status === 'en_progreso') {
            $timeLeft = max(0, $now->diffInSeconds(Carbon::parse($group->end_time), false));
        } elseif ($group->status === 'pausado') {
            // En pausa, tiempo restante se calcula igual, pero no debe avanzar
            $timeLeft = max(0, $now->diffInSeconds(Carbon::parse($group->end_time), false));
        } else {
            // Para estados completado o pendiente
            $timeLeft = 0;
        }

        // 🔥 Estado si ya terminó por tiempo
        $examCompleted = ($group->status === 'completado') || ($timeLeft <= 0 && $group->status === 'en_progreso');

        // 🔥 Si el examen está pausado, el alumno NO puede responder
        $canAnswer = ($group->status === 'en_progreso' && $timeLeft > 0);

        return response()->json([
            'group' => $group,
            'status' => $group->status,
            'start_time' => $group->start_time,
            'end_time' => $group->end_time,
            'time_left' => $timeLeft,
            'examCompleted' => $examCompleted,
            'canAnswer' => $canAnswer,
        ]);
    }

    public function updateStatusGroup(Request $request, string $id)
    {
        $group = Group::find($id);
        if (!$group) {
            return response()->json(['message' => 'Grupo no encontrado.'], 404);
        }

        $group->status = 'completado';
        $group->save();

        return response()->json(['message' => 'Estado del grupo actualizado correctamente.', 'group' => $group]);
    }
    public function create(ValidationGroup $request)
    {
        DB::beginTransaction();

        try {
            $start = Carbon::parse($request->start_time);
            $end   = Carbon::parse($request->end_time);

            if ($end->lessThanOrEqualTo($start)) {
                return response()->json([
                    'message' => 'La fecha/hora de fin debe ser mayor que la de inicio.'
                ], 422);
            }

            // Validación: laboratorio único
            $laboratory = Laboratorie::find($request->laboratory_id);

            if (!$laboratory) {
                return response()->json([
                    'message' => 'El laboratorio no existe.'
                ], 404);
            }

            // Validar solapamiento
            $exists = Group::where('laboratory_id', $laboratory->id)
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_time', [$start, $end])
                        ->orWhereBetween('end_time', [$start, $end])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->where('start_time', '<=', $start)
                                ->where('end_time', '>=', $end);
                        });
                })
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => "Ya existe un grupo en el laboratorio '{$laboratory->name}' que se solapa con ese horario."
                ], 409);
            }

            // Crear grupo único
            $group = Group::create([
                'evaluation_id'  => $request->evaluation_id,
                'laboratory_id'  => $laboratory->id,
                'name'           => $request->name,
                'description'    => $request->description ?? '',
                'total_students' => 0,
                'start_time'     => $start,
                'end_time'       => $end,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Grupo creado correctamente.',
                'group'   => $group->load('lab')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error interno del servidor.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(ValidationGroup $request, string $id)
    {
        // 1. Buscar el grupo
        $group = Group::find($id);
        if (!$group) {
            return response()->json(['message' => 'Grupo no encontrado.'], 404);
        }

        // 2. Actualizar los campos básicos
        if ($request->has('name')) {
            $group->name = $request->name;
        }

        if ($request->has('description')) {
            $group->description = $request->description;
        }

        // Para mantener coherencia con la fecha de realización de la evaluación
        $evaluation = Evaluation::find($group->evaluation_id);
        if ($evaluation) {
            $examDate = Carbon::parse($evaluation->date_of_realization);

            if ($request->has('start_time')) {
                $group->start_time = Carbon::parse($examDate->format('Y-m-d') . ' ' . $request->start_time);
            }

            if ($request->has('end_time')) {
                $group->end_time = Carbon::parse($examDate->format('Y-m-d') . ' ' . $request->end_time);
            }
        }

        $group->save();

        return response()->json([
            'group' => $group,
            'message' => 'Grupo actualizado correctamente.'
        ], 200);
    }

    public function startGroupEvaluation(Request $request, $groupId)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Token no encontrado'], 401);
        }

        $group = Group::with('evaluation')->find($groupId);
        if (!$group) {
            return response()->json(['message' => 'Grupo no encontrado'], 404);
        }

        $startTime = now();
        $duration = $group->evaluation->time ?? 0; // en minutos
        $segundos = $duration * 60;
        $endTime = $startTime->copy()->addMinutes($duration);
        $date = $group->evaluation->date_of_realization;
        // Validar fecha
        if (Carbon::parse($date)->format('Y-m-d') !== $startTime->format('Y-m-d')) {
            return response()->json(['message' => 'La evaluación no está programada para hoy'], 400);
        }

        // ✅ Solo se puede iniciar si está pendiente
        if ($group->status !== 'pendiente') {
            return response()->json(['message' => 'El examen ya fue iniciado o no está pendiente'], 400);
        }

        DB::beginTransaction();

        $group->start_time = $startTime;
        $group->end_time = $endTime;
        $group->status = 'en_progreso'; // ✅ Cambiar estado
        $group->save();

        StudentTest::whereIn('student_id', function ($query) use ($groupId) {
            $query->select('student_id')
                ->from('group_student')
                ->where('group_id', $groupId);
        })
            ->where('evaluation_id', $group->evaluation_id)
            ->update([
                'start_time' => $startTime->format('H:i:s'),
                'updated_at' => now()
            ]);

        DB::commit();

        return response()->json([
            'message' => 'Examen iniciado correctamente',
            'start_time' => $group->start_time,
            'end_time' => $group->end_time,
            'duration' => $segundos,
            'status' => $group->status
        ]);
    }

    public function pauseGroupEvaluation(Request $request, $groupId)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Token no encontrado'], 401);
        }

        $group = Group::with('evaluation')->find($groupId);
        if (!$group) {
            return response()->json(['message' => 'Grupo no encontrado'], 404);
        }

        if ($group->status !== 'en_progreso') {
            return response()->json(['message' => 'El examen no está en curso'], 400);
        }

        try {
            $now = now();
            $end = Carbon::parse($group->end_time);

            // 🔥 Calcular tiempo restante
            $timeLeft = max(0, $now->diffInSeconds($end, false));

            // 🔥 Guardar el tiempo restante para reanudarlo después
            $group->time_left = $timeLeft;

            // 🔥 Congelar el examen
            $group->status = 'pausado';

            $group->save();

            return response()->json([
                'message' => 'Examen pausado correctamente',
                'status'  => $group->status,
                'time_left' => $timeLeft
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al pausar el examen',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function continueGroupEvaluation(Request $request, $groupId)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Token no encontrado'], 401);
        }

        $group = Group::find($groupId);
        if (!$group) {
            return response()->json(['message' => 'Grupo no encontrado'], 404);
        }

        if ($group->status !== 'pausado') {
            return response()->json(['message' => 'El examen no está pausado'], 400);
        }

        try {
            $now = now();
            $newEndTime = $now->copy()->addSeconds($group->time_left);

            $group->status = 'en_progreso';
            $group->end_time = $newEndTime;
            $group->time_left = null; // ya no hace falta

            $group->save();

            return response()->json([
                'message' => 'Examen reanudado correctamente',
                'status' => $group->status,
                'end_time' => $group->end_time
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al reanudar el examen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function stopGroupEvaluation($groupId)
    {
        $group = Group::with('evaluation')->find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Grupo no encontrado'], 404);
        }

        if (!$group->start_time) {
            return response()->json(['message' => 'El examen no ha sido iniciado'], 400);
        }

        if ($group->status === 'completado') {
            return response()->json(['message' => 'El examen ya fue finalizado'], 400);
        }

        DB::beginTransaction();

        try {
            // Actualizar grupo
            $group->end_time = now();
            $group->status = 'completado';
            $group->save();

            // Obtener student_tests de los estudiantes del grupo
            $studentTests = StudentTest::whereIn('student_id', function ($query) use ($groupId) {
                $query->select('student_id')
                    ->from('group_student')
                    ->where('group_id', $groupId);
            })
                ->where('evaluation_id', $group->evaluation_id)
                ->get();

            foreach ($studentTests as $studentTest) {
                // Si ya tiene end_time, no actualizarlo
                if (is_null($studentTest->end_time)) {
                    // Calcular total de preguntas desde el array questions_order
                    $totalQuestions = is_array($studentTest->questions_order)
                        ? count($studentTest->questions_order)
                        : count(json_decode($studentTest->questions_order, true));

                    $studentTest->status = 'completado';
                    $studentTest->not_answered = $totalQuestions - ($studentTest->correct_answers + $studentTest->incorrect_answers);
                    $studentTest->end_time = now()->format('H:i:s');
                    $studentTest->updated_at = now();
                    $studentTest->save();
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Examen detenido correctamente para el grupo',
                'group_id' => $group->id,
                'end_time' => $group->end_time
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al detener el examen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //actualizar estado y hora final para todos los estudiantes del grupo
    public function finalizeGroupEvaluation($groupId)
    {
        $group = Group::with('evaluation')->find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Grupo no encontrado'], 404);
        }

        if (!$group->start_time) {
            return response()->json(['message' => 'El examen no ha sido iniciado'], 400);
        }

        DB::beginTransaction();

        try {
            // 🔹 Procesar primero los student_tests en progreso (guardar sus respuestas antes de cerrar grupo)
            $studentTestsInProgress = StudentTest::whereIn('student_id', function ($q) use ($groupId) {
                $q->select('student_id')->from('group_student')->where('group_id', $groupId);
            })
                ->where('evaluation_id', $group->evaluation_id)
                ->where('status', 'en_progreso')
                ->get();

            foreach ($studentTestsInProgress as $studentTest) {
                // 👇 Invocamos la misma lógica que usas en bulkSave
                app()->call([self::class, 'bulkSave'], [
                    'request' => new Request([
                        'student_test_id' => $studentTest->id,
                        'finalize' => true
                    ])
                ]);
            }

            // 🔹 Marcar estudiantes que nunca iniciaron como completados con 0 puntos
            $studentTestsPending = StudentTest::whereIn('student_id', function ($q) use ($groupId) {
                $q->select('student_id')->from('group_student')->where('group_id', $groupId);
            })
                ->where('evaluation_id', $group->evaluation_id)
                ->where('status', 'pendiente')
                ->get();

            foreach ($studentTestsPending as $studentTest) {
                $totalQuestions = is_array($studentTest->questions_order)
                    ? count($studentTest->questions_order)
                    : count(json_decode($studentTest->questions_order, true));

                $studentTest->update([
                    'status' => 'completado',
                    'correct_answers' => 0,
                    'incorrect_answers' => 0,
                    'not_answered' => $totalQuestions,
                    'end_time' => now()->format('H:i:s'),
                    'updated_at' => now(),
                ]);

                Result::where('student_test_id', $studentTest->id)
                    ->update([
                        'status' => 'no_se_presento',
                        'qualification' => 0,
                        'exam_duration' => '00:00:00',
                        'updated_at' => now(),
                    ]);
            }

            // 🔹 Ahora sí, marcar grupo como finalizado
            $group->update([
                'end_time' => now(),
                'status' => 'completado'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Examen finalizado correctamente. Se guardaron todas las respuestas antes de cerrar el grupo.',
                'group_id' => $group->id,
                'end_time' => $group->end_time
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al finalizar el examen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listFinalResultsByGroup($groupId)
    {
        $group = Group::with('evaluation')->find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Grupo no encontrado'], 404);
        }

        $evaluation = $group->evaluation;

        $studentTests = StudentTest::with('student')
            ->where('evaluation_id', $evaluation->id)
            ->whereIn('student_id', function ($query) use ($groupId) {
                $query->select('student_id')
                    ->from('group_student')
                    ->where('group_id', $groupId);
            })
            ->get();

        if ($studentTests->isEmpty()) {
            return response()->json([
                'message' => 'Aún no hay estudiantes asignados o el examen no ha iniciado'
            ], 404);
        }

        // Separar según estado
        $completedTests = $studentTests->where('status', 'completado');
        $inProgressTests = $studentTests->where('status', 'en_progreso');

        if ($completedTests->isEmpty()) {
            if ($inProgressTests->isNotEmpty()) {
                return response()->json([
                    'message' => 'El examen está en progreso. Los resultados aún no están disponibles.'
                ], 200);
            }
            return response()->json([
                'message' => 'El examen aún no ha iniciado.'
            ], 200);
        }

        // Mapear resultados solo de los completados
        $results = $completedTests->map(function ($test) {
            $student = $test->student;
            $fullName = $student->name
                . ' ' . ($student->paternal_surname ?? '')
                . ' ' . ($student->maternal_surname ?? '');

            $examDuration = 0;
            $formattedDuration = '00:00:00';
            if ($test->start_time && $test->end_time) {
                $examDuration = $test->end_time->diffInSeconds($test->start_time);
                $formattedDuration = gmdate('H:i:s', $examDuration);
            }

            return [
                'student_name'   => trim($fullName),
                'student_ci'     => $student->ci,
                'score_obtained' => $test->score_obtained ?? 0,
                'exam_duration'  => $formattedDuration,
                'duration_seconds' => $examDuration, // se usa solo para ordenar
                'status'         => $test->status,
            ];
        })
            // Ordenar primero por calificación descendente y luego por menor tiempo
            ->sortBy([
                ['score_obtained', 'desc'],
                ['duration_seconds', 'asc'],
            ])
            // Reindexar y eliminar el campo auxiliar
            ->values()
            ->map(function ($item) {
                unset($item['duration_seconds']);
                return $item;
            });

        return response()->json([
            'evaluation'       => $evaluation->title,
            'group'            => $group->name,
            'passing_score'    => $evaluation->passing_score,
            'students_results' => $results,
        ]);
    }

    public function asignStudentsToGroup(Request $request, $groupId)
    {
        $group = Group::find($groupId);
        if (!$group) {
            return response()->json(['message' => 'Grupo no encontrado'], 404);
        }

        // Obtener laboratorio asociado
        $laboratory = Laboratorie::find($group->laboratory_id);
        if (!$laboratory) {
            return response()->json(['message' => 'Laboratorio no encontrado'], 404);
        }

        $capacidad = $laboratory->equipment_count;

        // Equipos que se deben reservar (puedes cambiarlo a 4)
        $equipos_reservados = 5;

        // Capacidad real disponible
        $capacidad_disponible = max(0, $capacidad - $equipos_reservados);

        // Orden enviado desde el frontend
        $orden = $request->order_type ?? 'apellido';

        // Obtener estudiantes no asignados aún
        if ($orden === 'apellido') {
            $students = Student::orderBy('paternal_surname', 'ASC')->get();
        } else {
            $students = Student::orderBy('id', 'ASC')->get();
        }

        // Limitar cantidad según capacidad disponible
        $studentsToAssign = $students->take($capacidad_disponible);

        // Limpiar asignaciones previas del grupo
        $group->students()->detach();

        // Asignar estudiantes al grupo
        foreach ($studentsToAssign as $student) {
            $group->students()->attach($student->id);
        }

        // Actualizar cantidad final
        $group->total_students = $studentsToAssign->count();
        $group->save();

        return response()->json([
            'message' => 'Estudiantes asignados automáticamente',
            'total_students' => $group->total_students,
            'capacidad_laboratorio' => $capacidad,
            'equipos_reservados' => $equipos_reservados,
            'capacidad_ocupada' => $capacidad_disponible
        ]);
    }
}
