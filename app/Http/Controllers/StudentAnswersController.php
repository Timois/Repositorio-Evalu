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
    public function store(Request $request)
    {
        $request->validate([
            'student_test_id' => 'required|integer|exists:student_tests,id',
        ]);

        $studentTestId = $request->input('student_test_id');
        $studentTest = StudentTest::with('evaluation')->findOrFail($studentTestId);

        // ✅ Evitar doble envío
        if ($studentTest->status === 'completado') {
            return response()->json([
                'message' => 'Ya has finalizado esta evaluación.'
            ], 409);
        }

        $totalScore = 0;
        $correctCount = 0;
        $incorrectCount = 0;

        // 🔎 Tomamos la última respuesta de logs (is_ultimate = true)
        $ultimateLogs = LogsAnswer::where('student_test_id', $studentTestId)
            ->where('is_ultimate', true)
            ->get();

        foreach ($ultimateLogs as $log) {
            $studentQuestion = StudentTestQuestion::find($log->student_test_question_id);
            if (!$studentQuestion) {
                continue;
            }

            $bankAnswer = AnswerBank::find($log->answer_id);
            $isCorrect = $bankAnswer && $bankAnswer->is_correct;

            $score = $isCorrect ? $studentQuestion->score_assigned : 0;

            // ✅ Ahora sí actualizamos student_test_questions
            $studentQuestion->update([
                'student_answer' => $log->answer_id,
                'is_correct' => $isCorrect,
                'score_assigned' => $score,
            ]);

            $totalScore += $score;
            $isCorrect ? $correctCount++ : $incorrectCount++;
        }

        $totalQuestions = StudentTestQuestion::where('student_test_id', $studentTestId)->count();
        $notAnsweredCount = $totalQuestions - $ultimateLogs->count();

        // Actualizar examen como completado
        $studentTest->update([
            'end_time' => now()->format('H:i:s'),
            'score_obtained' => $totalScore,
            'correct_answers' => $correctCount,
            'incorrect_answers' => $incorrectCount,
            'not_answered' => $notAnsweredCount,
            'status' => 'completado',
        ]);

        // Duración
        $duration = \Carbon\Carbon::parse($studentTest->start_time)->diff(now())->format('%H:%I:%S');

        // Evaluación y resultado
        $evaluation = $studentTest->evaluation;
        $status = $totalScore >= $evaluation->passing_score ? 'admitido' : 'no_admitido';

        $result = Result::create([
            'student_test_id' => $studentTestId,
            'qualification'   => $totalScore,
            'maximum_score'   => 0, // temporal
            'minimum_score'   => 0, // temporal
            'exam_duration'   => $duration,
            'status'          => $status,
        ]);

        // Calcular min y max
        $scores = Result::whereHas('studentTest', fn($q) => $q->where('evaluation_id', $evaluation->id))
            ->pluck('qualification');
        $result->update([
            'minimum_score' => $scores->min() ?? 0,
            'maximum_score' => $scores->max() ?? 0,
        ]);

        return response()->json([
            'message' => 'Examen finalizado y respuestas guardadas.',
            'qualification' => $totalScore,
            'status' => $status,
            'min_score' => $scores->min(),
            'max_score' => $scores->max(),
            'passing_score' => $evaluation->passing_score,
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
