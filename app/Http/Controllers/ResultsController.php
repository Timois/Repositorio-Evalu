<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Student;
use App\Models\StudentTest;
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
                'student_name' => $test->student->full_name ?? null, // Asume que tienes esto
                'score_obtained' => $test->score_obtained,
                'correct_answers' => $test->correct_answers,
                'incorrect_answers' => $test->incorrect_answers,
                'not_answered' => $test->not_answered,
                'duration_minutes' => $duration,
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
}
