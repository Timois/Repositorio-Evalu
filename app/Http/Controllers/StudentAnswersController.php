<?php

namespace App\Http\Controllers;

use App\Models\StudentAnswer;
use Illuminate\Http\Request;

class StudentAnswersController extends Controller
{
    public function store(Request $request)
    {
        // Validación básica
        $request->validate([
            'student_test_id' => 'required|integer|exists:student_tests,id',
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer|exists:bank_questions,id',
            'answers.*.answer_id' => 'required|integer|exists:bank_answers,id',
        ]);
        
        $studentTestId = $request->input('student_test_id');
        $answers = $request->input('answers');

        foreach ($answers as $answerData) {
            // Aquí puedes agregar lógica para no duplicar respuestas si quieres
            StudentAnswer::updateOrCreate(
                [
                    'student_test_id' => $studentTestId,
                    'question_id' => $answerData['question_id'],
                ],
                [
                    'answer_id' => $answerData['answer_id'],
                    'score' => 0, // puedes calcular o actualizar después la nota
                ]
            );
        }

        return response()->json(['message' => 'Respuestas guardadas correctamente'], 201);
    }
}
