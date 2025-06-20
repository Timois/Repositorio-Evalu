<?php

namespace App\Http\Controllers;

use App\Models\AnswerBank;
use App\Models\StudentTest;
use App\Models\StudentTestQuestion;
use App\Models\UserStudent;
use Illuminate\Http\Request;

class StudentAnswersController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'student_test_id' => 'required|integer|exists:student_tests,id',
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer|exists:bank_questions,id',
            'answers.*.answer_id' => 'required|integer|exists:bank_answers,id',
        ]);

        $studentTestId = $request->input('student_test_id');
        $answers = $request->input('answers');

        // Verificar si ya respondió
        $alreadyAnswered = StudentTestQuestion::where('student_test_id', $studentTestId)
            ->whereNotNull('student_answer')
            ->exists();

        if ($alreadyAnswered) {
            return response()->json([
                'message' => 'Ya has respondido esta evaluación. No puedes enviar respuestas nuevamente.',
            ], 409);
        }

        $totalScore = 0;
        $correctCount = 0;
        $incorrectCount = 0;

        foreach ($answers as $answerData) {
            $questionId = $answerData['question_id'];
            $answerId = $answerData['answer_id'];

            $bankAnswer = AnswerBank::find($answerId);
            $isCorrect = $bankAnswer && $bankAnswer->is_correct;

            // Obtener puntaje asignado a esa pregunta
            $studentTestQuestion = StudentTestQuestion::where('student_test_id', $studentTestId)
                ->where('question_id', $questionId)
                ->first();

            $score = 0;
            if ($studentTestQuestion) {
                $score = $isCorrect ? $studentTestQuestion->score_assigned : 0;

                // Actualizar la respuesta del estudiante en la tabla
                $studentTestQuestion->update([
                    'student_answer' => $answerId,
                    'is_correct' => $isCorrect,
                    'score_assigned' => $score,
                ]);
            }

            $totalScore += $score;
            if ($isCorrect) {
                $correctCount++;
            } else {
                $incorrectCount++;
            }
        }

        // Calcular preguntas no respondidas
        $totalQuestions = StudentTestQuestion::where('student_test_id', $studentTestId)->count();
        $notAnsweredCount = $totalQuestions - count($answers);

        // Actualizar resumen en student_tests
        $studentTest = StudentTest::find($studentTestId);
        $studentTest->update([
            'end_time' => now()->format('H:i:s'),
            'score_obtained' => $totalScore,
            'correct_answers' => $correctCount,
            'incorrect_answers' => $incorrectCount,
            'not_answered' => $notAnsweredCount,
            'status' => 'completado', // actualizado a "completado"
        ]);

        return response()->json([
            'message' => 'Respuestas guardadas correctamente',
            'total_score' => $totalScore,
        ], 201);
    }


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
