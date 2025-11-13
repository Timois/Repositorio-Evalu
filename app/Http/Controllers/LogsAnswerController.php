<?php

namespace App\Http\Controllers;

use App\Models\AnswerBank;
use App\Models\LogsAnswer;
use App\Models\StudentTest;
use App\Models\StudentTestQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogsAnswerController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'student_test_id' => 'required|exists:student_tests,id',
            'question_id' => 'required|exists:bank_questions,id',
            'answer_id' => 'nullable|integer',
            'time' => 'nullable|string' // formato HH:MM:SS
        ]);

        try {
            DB::beginTransaction();

            // Buscar la pregunta de ese test
            $studentQuestion = StudentTestQuestion::where('student_test_id', $request->student_test_id)
                ->where('question_id', $request->question_id)
                ->first();

            if (!$studentQuestion) {
                return response()->json(['error' => 'Pregunta no encontrada para este examen'], 404);
            }

            // Actualizar la respuesta actual del estudiante
            $studentQuestion->update([
                'student_answer' => $request->student_answer,
                'is_correct' => null // Se puede calcular después
            ]);

            // Marcar los logs anteriores como no últimos
            LogsAnswer::where('student_test_question_id', $studentQuestion->id)
                ->update(['is_ultimate' => false]);

            // Guardar nuevo log
            LogsAnswer::create([
                'student_test_id' => $request->student_test_id,
                'student_test_question_id' => $studentQuestion->id,
                'answer_id' => $request->answer_id,
                'time' => $request->time ?? now()->format('H:i:s'),
                'is_ultimate' => true
            ]);

            DB::commit();

            return response()->json(['message' => 'Respuesta guardada correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al guardar la respuesta.', 'details' => $e->getMessage()], 500);
        }
    }
    public function bulkSave(Request $request)
    {
        $request->validate([
            'student_test_id' => 'required|integer|exists:student_tests,id',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:bank_questions,id',
            'answers.*.answer_id' => 'nullable|integer',
            'answers.*.time' => 'nullable|string', // formato HH:MM:SS
            'finalize' => 'nullable|in:true,false',    // 🔹 true si es cierre de examen
        ]);

        try {
            DB::beginTransaction();

            $studentTestId = $request->student_test_id;
            $studentTest = StudentTest::with('evaluation')->findOrFail($studentTestId);

            // 🚫 Evitar doble envío si ya está finalizado y NO viene con finalize
            if ($studentTest->status === 'completado' && !$request->boolean('finalize')) {
                return response()->json([
                    'message' => 'Ya has finalizado esta evaluación.'
                ], 409);
            }

            // 🔹 Guardar/actualizar respuestas + logs
            foreach ($request->answers as $answer) {
                $studentQuestion = StudentTestQuestion::where('student_test_id', $studentTestId)
                    ->where('question_id', $answer['question_id'])
                    ->first();

                if (!$studentQuestion) continue;

                // Actualizar respuesta del estudiante
                $studentQuestion->update([
                    'student_answer' => $answer['answer_id'],
                    'is_correct' => null
                ]);

                // Marcar logs anteriores como no últimos
                LogsAnswer::where('student_test_question_id', $studentQuestion->id)
                    ->update(['is_ultimate' => false]);

                // Guardar nuevo log
                LogsAnswer::create([
                    'student_test_id' => $studentTestId,
                    'student_test_question_id' => $studentQuestion->id,
                    'answer_id' => $answer['answer_id'],
                    'time' => $answer['time'] ?? now()->format('H:i:s'),
                    'is_ultimate' => true
                ]);
            }

            // 🔹 Si NO es finalización → solo guardar logs
            if (!$request->boolean('finalize')) {
                DB::commit();
                return response()->json(['message' => 'Respuestas guardadas correctamente.']);
            }

            // 🔹 Si es finalización → calcular nota y cerrar examen
            $totalScore = 0;
            $correctCount = 0;
            $incorrectCount = 0;

            // Últimas respuestas desde logs
            $ultimateLogs = LogsAnswer::where('student_test_id', $studentTestId)
                ->where('is_ultimate', true)
                ->get();

            foreach ($ultimateLogs as $log) {
                $studentQuestion = StudentTestQuestion::find($log->student_test_question_id);
                if (!$studentQuestion) continue;

                $bankAnswer = AnswerBank::find($log->answer_id);
                $isCorrect = $bankAnswer && $bankAnswer->is_correct;

                $score = $isCorrect ? $studentQuestion->score_assigned : 0;

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

            // Actualizar estado de la evaluación
            $evaluation = $studentTest->evaluation;
            if ($evaluation) {
                $evaluation->status = 'inactivo';
                $evaluation->save();
            }
            DB::commit();
            return response()->json([
                'message' => 'Respuestas guardadas correctamente.',
                'score' => $totalScore
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al guardar respuestas.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // Recuperar las respuestas ya respondidas por el estudiante
    public function getAnswers($id)
    {
        // Buscar el StudentTest por ID
        $studentTest = StudentTest::find($id);

        // Verificar si el registro existe
        if (!$studentTest) {
            return response()->json(['message' => 'StudentTest no encontrado'], 404);
        }

        // Recuperar las respuestas asociadas al student_test_id
        $answers = LogsAnswer::where('student_test_id', $id)->get();

        return response()->json($answers);
    }
}
