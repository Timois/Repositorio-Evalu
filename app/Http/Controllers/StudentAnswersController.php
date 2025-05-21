<?php

namespace App\Http\Controllers;

use App\Models\AnswerBank;
use App\Models\Evaluation;
use App\Models\QuestionBank;
use App\Models\QuestionEvaluation;
use App\Models\StudentAnswer;
use App\Models\StudentTest;
use App\Models\UserStudent;
use Illuminate\Http\Request;

class StudentAnswersController extends Controller
{
    public function startTest(Request $request)
    {
        $request->validate([
            'evaluation_id' => 'required|integer|exists:evaluations,id',
            'student_id' => 'required|integer|exists:students,id',
        ]);

        // Buscar si ya existe el test para ese estudiante y evaluación
        $studentTest = StudentTest::where('evaluation_id', $request->evaluation_id)
            ->where('student_id', $request->student_id)
            ->first();
        // 🔁 Obtener el estudiante y actualizar su estado a "evaluando"
        $student = UserStudent::find($request->student_id);
        if (!$student) {
            return response()->json(['message' => 'Estudiante no encontrado'], 404);
        }
        $student->update(['status' => 'evaluando']);
        if (!$studentTest) {
            // Crear registro nuevo con start_time actual
            $studentTest = StudentTest::create([
                'evaluation_id' => $request->evaluation_id,
                'student_id' => $request->student_id,
                'code' => (string) \Illuminate\Support\Str::uuid(),
                'start_time' => now()->format('H:i:s'),
                'status' => 'evaluado',
            ]);
        } else {
            // Si ya existe, solo actualizamos start_time si no está definido
            if (!$studentTest->start_time) {
                $studentTest->update([
                    'start_time' => now()->format('H:i:s'),
                ]);
            }
        }    

        return response()->json([
            'message' => 'Examen iniciado correctamente',
            'student_test_id' => $studentTest->id,
            'start_time' => $studentTest->start_time,
            'code' => $studentTest->code,
        ], 200);
    }

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

        $alreadyAnswered = StudentAnswer::where('student_test_id', $studentTestId)->exists();
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

            $score = 0;
            if ($isCorrect) {
                $questionEval = QuestionEvaluation::where('question_id', $questionId)->first();
                if ($questionEval) {
                    $score = $questionEval->score;
                }
                $correctCount++;
            } else {
                $incorrectCount++;
            }

            $totalScore += $score;

            StudentAnswer::create([
                'student_test_id' => $studentTestId,
                'question_id' => $questionId,
                'answer_id' => $answerId,
                'score' => $score,
            ]);
        }

        // Contar preguntas no respondidas (si aplicable)
        $studentTest = StudentTest::find($studentTestId);
        $evaluationId = $studentTest->evaluation_id;
        $totalQuestions = QuestionBank::where('evaluation_id', $evaluationId)->count();
        $notAnsweredCount = $totalQuestions - count($answers);

        // Actualizar student_tests con resultado y fin del examen
        $studentTest->update([
            'end_time' => now()->format('H:i:s'),
            'score_obtained' => $totalScore,
            'correct_answers' => $correctCount,
            'incorrect_answers' => $incorrectCount,
            'not_answered' => $notAnsweredCount,
            'status' => 'corregido',
        ]);

        return response()->json([
            'message' => 'Respuestas guardadas correctamente',
            'total_score' => $totalScore,
        ], 201);
    }

    public function hasAnswered($studentTestId)
    {
        $answered = StudentAnswer::where('student_test_id', $studentTestId)->exists();

        if ($answered) {
            $totalScore = StudentAnswer::where('student_test_id', $studentTestId)->sum('score');
            return response()->json([
                'answered' => true,
                'score' => $totalScore
            ]);
        }

        return response()->json(['answered' => false]);
    }
}
