<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationStudentTest;
use App\Models\Student;
use App\Models\StudentTest;

class StudenTestsController extends Controller
{
    public function find($evaluationId)
    {
        $students = StudentTest::with('student')
            ->where('evaluation_id', $evaluationId)
            ->orderBy('id', 'asc')
            ->get();

        // Retornamos solo los datos del estudiante
        $studentData = $students->pluck('student');

        return response()->json($studentData);
    }

    public function findById(string $id)
    {
        $test = StudentTest::find($id);
        if (!$test)
            return ["message:", "La prueba con id:" . $id . " no existe."];
        return response()->json($test);
    }
    public function findIdByCi(string $ci)
    {
        $id = Student::where('ci', $ci)->first();
        if (!$id)
            return ["message:", "El estudiante con ci:" . $ci . " no existe."];
        return response()->json($id);
    }

    public function findAndUpdate(ValidationStudentTest $request, string $id)
    {
        $test = StudentTest::find($id);
        if (!$test)
            return ["message:", "La prueba con id:" . $id . " no existe."];
        if ($request->name)
            $test->name = $request->name;
        if ($request->code)
            $test->code = $request->code;
        if ($request->start_time)
            $test->start_time = $request->start_time;
        if ($request->end_time)
            $test->end_time = $request->end_time;
        if ($request->score_obtained)
            $test->score_obtained = $request->score_obtained;
        if ($request->correct_answers)
            $test->correct_answers = $request->correct_answers;
        if ($request->incorrect_answers)
            $test->incorrect_answers = $request->incorrect_answers;
        if ($request->not_answered)
            $test->not_answered = $request->not_answered;
        if ($request->status)
            $test->status = $request->status;
        $test->save();
        return response()->json($test);
    }


    public function getStudentsByEvaluation($evaluationId)
    {
        $students = StudentTest::with('student') // Asegúrate de tener la relación definida
            ->where('evaluation_id', $evaluationId)
            ->get();

        return response()->json($students);
    }

    // Reportes de calificaciones por examen
    public function getStudentsScoresByEvaluation($evaluationId)
    {
        // Obtener pruebas completadas por los estudiantes
        $studentTests = StudentTest::with('student')
            ->where('evaluation_id', $evaluationId)
            ->whereNotNull('score_obtained')
            ->orderByDesc('score_obtained')
            ->orderByRaw("EXTRACT(EPOCH FROM (end_time - start_time)) ASC") // menor tiempo primero
            ->get();

        // Formatear la lista
        $report = $studentTests->map(function ($test) {
            if ($test->end_time && $test->start_time) {
                $start = \Carbon\Carbon::parse($test->start_time);
                $end = \Carbon\Carbon::parse($test->end_time);
                $diffSeconds = $end->diffInSeconds($start);

                // Convertir a horas:minutos:segundos
                $hours = floor($diffSeconds / 3600);
                $minutes = floor(($diffSeconds % 3600) / 60);
                $seconds = $diffSeconds % 60;

                $formattedDuration = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            } else {
                $formattedDuration = null;
            }

            return [
                'ci' => $test->student->ci,
                'name' => $test->student->name,
                'paternal_surname' => $test->student->paternal_surname,
                'maternal_surname' => $test->student->maternal_surname,
                'student_test_id' => $test->id,
                'code' => $test->code,
                'score' => $test->score_obtained,
                'hora_inicio' => $test->start_time,
                'hora_fin' => $test->end_time,
                'duracion' => $formattedDuration, // HH:MM:SS
                'status' => $test->status
            ];
        });

        return response()->json([
            'total_students' => $report->count(),
            'students' => $report,
        ]);
    }

    public function getGaussianCurve($evaluationId)
    {
        // Obtener los tests completados con nota
        $scores = StudentTest::where('evaluation_id', $evaluationId)
            ->where('status', 'completado')
            ->whereNotNull('score_obtained')
            ->pluck('score_obtained'); // solo las notas

        $count = $scores->count();
        if ($count === 0) {
            return response()->json(['message' => 'No hay datos disponibles.']);
        }

        // Media (μ)
        $mean = $scores->avg();

        // Desviación estándar (σ)
        $variance = $scores->reduce(function ($carry, $score) use ($mean) {
            return $carry + pow($score - $mean, 2);
        }, 0) / $count;

        $stdDeviation = sqrt($variance);

        // Clasificación de notas
        $classified = $scores->map(function ($score) use ($mean, $stdDeviation) {
            if ($score >= $mean + $stdDeviation) {
                $category = 'sobresaliente';
            } elseif ($score <= $mean - $stdDeviation) {
                $category = 'bajo';
            } else {
                $category = 'promedio';
            }
            return [
                'score' => $score,
                'category' => $category,
            ];
        });

        return response()->json([
            'count' => $count,
            'mean' => round($mean, 2),
            'standard_deviation' => round($stdDeviation, 2),
            'distribution' => $classified->groupBy('category')->map->count(),
            'detailed_scores' => $classified->sortByDesc('score')->values(),
        ]);
    }
}
