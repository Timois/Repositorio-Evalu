<?php

namespace App\Http\Controllers;

use App\Models\AnswerBank;
use App\Models\LogsAnswer;
use App\Models\Result;
use App\Models\StudentTest;
use App\Models\StudentTestQuestion;
use Illuminate\Http\Request;

class StudentAnswersController extends Controller
{

    public function hasAnswered($studentTestId)
    {
        // Verifica si hay al menos una respuesta registrada (es decir, si el campo 'student_answer' no está nulo)
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
