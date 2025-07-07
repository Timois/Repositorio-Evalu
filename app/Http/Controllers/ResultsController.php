<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\Result;
use App\Models\Student;
use App\Models\StudentTest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ResultsController extends Controller
{
    public function find()
    {
        $results = Result::orderBy('id', 'asc')->get();
        return response()->json($results);
    }
    public function findById(string $id)
    {
        $result = Result::find($id);
        if ($result) {
            return response()->json($result);
        } else {
            return response()->json(['message' => 'No se encontro el resultado'], 404);
        }
    }

    public function showResultsByEvaluation($evaluationId)
    {
        $student = Student::find($evaluationId);
        if (!$student) {
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
                'student_id' => $test->student_id,
                'student_ci' => $test->student->ci,
                'score_obtained' => $test->score_obtained,
                'not_answered' => $test->not_answered,
                'exam_duration' => $duration,
                'status' => $test->status ?? 'pendiente',
            ];
        });

        // Cálculos globales para todos los estudiantes asignados a la evaluación
        $maxScore = $results->max('score_obtained');
        $minScore = $results->min('score_obtained');

        return response()->json([
            'evaluation_id' => $evaluationId,
            'max_score' => $maxScore,
            'min_score' => $minScore,
            'students_results' => $results,
        ]);
    }


    public function listFinalResultsByEvaluation($evaluationId)
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

        $maxScore = $studentTests->max('score_obtained');
        $minScore = $studentTests->min('score_obtained');

        $examDuration = $evaluation->time; // <-- duración total del examen desde evaluación

        $results = [];

        foreach ($studentTests as $test) {
            $score = $test->score_obtained;
            $status = $score >= $evaluation->passing_score ? 'admitido' : 'no_admitido';

            Result::updateOrCreate(
                ['student_test_id' => $test->id],
                [
                    'qualification' => $score,
                    'maximum_score' => $maxScore,
                    'minimum_score' => $minScore,
                    'exam_duration' => $examDuration,
                    'status' => $status,
                ]
            );

            $results[] = [
                'student_name' => $test->student->name,
                'student_ci' => $test->student->ci,
                'score_obtained' => $score,
                'exam_duration' => $examDuration,
                'status' => $status,
            ];
        }

        return response()->json([
            'evaluation' => $evaluation->title,
            'passing_score' => $evaluation->passing_score,
            'students_results' => $results,
            'resumen' => [
                'nota_maxima' => $maxScore,
                'nota_minima' => $minScore,
                'exam_duration' => $examDuration,
            ]
        ]);
    }
}
