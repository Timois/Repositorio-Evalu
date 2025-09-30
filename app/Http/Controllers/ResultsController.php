<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\Result;
use App\Models\Student;
use App\Models\StudentTest;
use Carbon\Carbon;

class ResultsController extends Controller
{
    public function listFinalResultsByEvaluation($evaluationId)
    {
        $evaluation = Evaluation::find($evaluationId);

        if (!$evaluation) {
            return response()->json(['message' => 'Evaluación no encontrada'], 404);
        }

        // Obtener todos los student_tests de la evaluación
        $studentTests = StudentTest::with('student')
            ->where('evaluation_id', $evaluationId)
            ->get();

        if ($studentTests->isEmpty()) {
            return response()->json(['message' => 'No hay estudiantes en esta evaluación'], 404);
        }

        // Verificar si existen pruebas sin finalizar
        $incompleteTests = $studentTests->filter(function ($test) {
            return $test->status !== 'completado'; // Ajusta el nombre del estado según tu modelo
        });

        if ($incompleteTests->isNotEmpty()) {
            return response()->json([
                'message' => 'La evaluación aún no ha concluido en todos los grupos. Existen pruebas sin finalizar.',
                'pending_students' => $incompleteTests->map(function ($test) {
                    return [
                        'student_name' => $test->student->name,
                        'student_ci'   => $test->student->ci,
                        'status'       => $test->status,
                    ];
                })->values()
            ], 400);
        }

        $results = [];
        $allScores = [];

        foreach ($studentTests as $test) {
            $score = $test->score_obtained;
            $start = \Carbon\Carbon::parse($test->start_time);
            $end = \Carbon\Carbon::parse($test->end_time);
            $examDuration = $start->diff($end)->format('%H:%I:%S');
            $status = $score >= $evaluation->passing_score ? 'admitido' : 'no_admitido';

            // Guardar o actualizar result
            Result::updateOrCreate(
                ['student_test_id' => $test->id],
                [
                    'qualification' => $score,
                    'exam_duration' => $examDuration,
                    'status'        => $status,
                ]
            );

            $allScores[] = $score;

            $results[] = [
                'student_name' => $test->student->name,
                'student_ci' => $test->student->ci,
                'score_obtained' => $score,
                'exam_duration' => $examDuration,
                'status' => $status,
            ];
        }

        $maximumScore = max($allScores);
        $minimumScore = min($allScores);

        return response()->json([
            'evaluation' => $evaluation->title,
            'passing_score' => $evaluation->passing_score,
            'students_results' => $results,
            'maximum_score' => $maximumScore,
            'minimum_score' => $minimumScore
        ]);
    }
}
