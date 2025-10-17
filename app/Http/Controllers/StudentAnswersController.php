<?php

namespace App\Http\Controllers;

use App\Models\StudentTest;
use App\Models\StudentTestQuestion;


class StudentAnswersController extends Controller
{

    public function hasAnswered($studentTestId)
    {
        // Obtener el registro de StudentTest
        $studentTest = StudentTest::find($studentTestId);

        if (!$studentTest) {
            return response()->json([
                'answered' => false,
                'message' => 'StudentTest no encontrado'
            ], 404);
        }

        // Si el examen está completado, devolvemos la nota final
        if ($studentTest->status === 'completado') {
            return response()->json([
                'answered' => true,
                'score' => $studentTest->score_obtained ?? 0 // si no tiene nota asignada, devolvemos 0
            ]);
        }

        // Si no está completado, verificamos si hay al menos una respuesta registrada
        $answered = StudentTestQuestion::where('student_test_id', $studentTestId)
            ->whereNotNull('student_answer')
            ->exists();

        if ($answered) {
            // Sumar solo los puntajes asignados a respuestas correctas
            $totalScore = StudentTestQuestion::where('student_test_id', $studentTestId)
                ->where('is_correct', true)
                ->sum('score_assigned');

            return response()->json([
                'answered' => true,
                'score' => $totalScore
            ]);
        }

        return response()->json(['answered' => false]);
    }
}
