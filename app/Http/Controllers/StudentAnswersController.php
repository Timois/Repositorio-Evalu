<?php

namespace App\Http\Controllers;

use App\Models\StudentTest;
use App\Models\StudentTestQuestion;


class StudentAnswersController extends Controller
{
    public function hasAnswered($studentTestId)
    {
        $studentTest = StudentTest::with('evaluation')->find($studentTestId);

        if (!$studentTest) {
            return response()->json([
                'answered' => false,
                'message' => 'El examen del estudiante no existe.',
            ], 404);
        }

        if ($studentTest->status === 'completado') {
            return response()->json([
                'answered' => true,
                'score' => $studentTest->score_obtained ?? 0,
                'view_score' => optional($studentTest->evaluation)->view_score ?? false
            ]);
        }

        return response()->json(['answered' => false]);
    }
}
