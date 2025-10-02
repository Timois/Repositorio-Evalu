<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\Result;
use App\Models\Student;
use App\Models\StudentTest;
use Carbon\Carbon;

class ResultsController extends Controller
{
    public function showResultsByEvaluation($evaluationId)
    {
        $evaluation = Evaluation::find($evaluationId);
        if (!$evaluation) {
            return response()->json(['message' => 'No se encontró la evaluación'], 404);
        }

        $studentTests = StudentTest::with('student')
            ->where('evaluation_id', $evaluationId)
            ->get();

        if ($studentTests->isEmpty()) {
            return response()->json(['message' => 'No hay estudiantes asignados a esta evaluación'], 404);
        }

        $results = $studentTests->map(function ($test) {
            $duration = null;

            if ($test->start_time && $test->end_time) {
                $start = \Carbon\Carbon::parse($test->start_time);
                $end = \Carbon\Carbon::parse($test->end_time);
                $duration = $start->diffInMinutes($end);
            }

            return [
                'student_id'     => $test->student_id,
                'student_ci'     => $test->student->ci,
                'score_obtained' => $test->score_obtained,
                'not_answered'   => $test->not_answered,
                'exam_duration'  => $duration,
                'status'         => $test->status ?? 'pendiente',
            ];
        });

        // Agrupamos estados
        $status = $studentTests->groupBy('status')->map->count();

        return response()->json([
            'evaluation_id'    => $evaluationId,
            'students_results' => $results,
            'status'   => $status,
        ]);
    }

    public function saveResults($evaluationId)
    {
        $evaluation = Evaluation::find($evaluationId);

        if (!$evaluation) {
            return response()->json(['message' => 'Evaluación no encontrada'], 404);
        }
        $studentTests = StudentTest::with('student')
            ->where('evaluation_id', $evaluationId)
            ->get();

        if ($studentTests->isEmpty()) {
            return response()->json(['message' => 'No hay estudiantes en esta evaluación'], 404);
        }

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
        }

        return response()->json(['message' => 'Resultados guardados correctamente'], 200);
    }
}
