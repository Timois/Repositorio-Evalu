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
    public function showResultsByEvaluation($evaluationId)
    {
        $evaluation = Evaluation::find($evaluationId);

        if (!$evaluation) {
            return response()->json(['message' => 'No se encontró la evaluación'], 404);
        }

        // 🔹 Obtenemos los resultados desde la tabla results, junto al estudiante
        $results = Result::with(['studentTest.student'])
            ->whereHas('studentTest', function ($query) use ($evaluationId) {
                $query->where('evaluation_id', $evaluationId);
            })
            ->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'No hay resultados registrados para esta evaluación'], 404);
        }

        // 🔹 Formateamos los datos de salida
        $formattedResults = $results->map(function ($result) {
            $studentTest = $result->studentTest;
            $student = $studentTest?->student;

            $duration = null;
            if ($studentTest && $studentTest->start_time && $studentTest->end_time) {
                $start = \Carbon\Carbon::parse($studentTest->start_time);
                $end = \Carbon\Carbon::parse($studentTest->end_time);
                $duration = $start->diffInMinutes($end);
            }

            return [
                'student_id'     => $student?->id,
                'student_ci'     => $student?->ci,
                'score_obtained' => $result->qualification,
                'exam_duration'  => $duration,
                'status'         => $result->status,
            ];
        });

        // 🔹 Agrupamos por estado
        $status = $formattedResults->groupBy('status')->map->count();

        return response()->json([
            'evaluation_id'    => $evaluationId,
            'students_results' => $formattedResults,
            'status'           => $status,
        ]);
    }


    public function saveResultsCurve(Request $request, $evaluationId)
    {
        $evaluation = Evaluation::find($evaluationId);

        if (!$evaluation) {
            return response()->json([
                'message' => 'Evaluación no encontrada'
            ], 404);
        }

        $validated = $request->validate([
            'min_score' => 'required|numeric|min:0|max:100',
            'results' => 'required|array',
            'results.*.student_test_id' => 'required|integer|exists:student_tests,id',
            'results.*.qualification' => 'required|numeric|min:0|max:100',
            'results.*.status' => 'required|string|in:admitido,no admitido,no se presento',
        ]);

        foreach ($validated['results'] as $resultData) {

            // 🔹 Traer el test con su estudiante
            $test = StudentTest::with('student')->find($resultData['student_test_id']);
            if (!$test) continue;

            $student = $test->student;

            $isInactive = $student && $student->status === 'inactivo';

            if ($isInactive) {
                $status = 'no se presento';
                $qualification = 0;
                $examDuration = '00:00:00';
            } else {
                $status = $resultData['status'];
                $qualification = $resultData['qualification'];

                $examDuration = '00:00:00';
                if ($test->start_time && $test->end_time) {
                    $start = \Carbon\Carbon::parse($test->start_time);
                    $end = \Carbon\Carbon::parse($test->end_time);
                    $examDuration = $start->diff($end)->format('%H:%I:%S');
                }
            }

            // 💾 Guardar resultado
            Result::updateOrCreate(
                ['student_test_id' => $test->id],
                [
                    'qualification' => $qualification,
                    'exam_duration' => $examDuration,
                    'status' => $status,
                ]
            );
        }

        // 🔹 Guardar nota mínima de aprobación
        $evaluation->update([
            'passing_score' => $validated['min_score']
        ]);

        return response()->json([
            'message' => 'Resultados actualizados según la curva correctamente ✅',
            'min_score' => $validated['min_score']
        ]);
    }
}
