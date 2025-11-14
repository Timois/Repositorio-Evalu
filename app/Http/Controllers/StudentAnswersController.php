<?php

namespace App\Http\Controllers;

use App\Models\StudentTest;
use App\Models\StudentTestQuestion;


class StudentAnswersController extends Controller
{

    public function hasAnswered($studentTestId)
    {
        $studentTest = StudentTest::find($studentTestId);

        if (!$studentTest) {
            return response()->json([
                'answered' => false,
                'message' => 'StudentTest no encontrado'
            ], 404);
        }

        // SOLO si está completado
        if ($studentTest->status === 'completado') {
            return response()->json([
                'answered' => true,
                'score' => $studentTest->score_obtained ?? 0,
            ]);
        }

        return response()->json(['answered' => false]);
    }
}
