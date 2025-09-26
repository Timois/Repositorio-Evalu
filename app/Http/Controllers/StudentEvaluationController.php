<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\Group;
use App\Models\Student;
use App\Models\StudentTest;
use App\Models\StudentTestQuestion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StudentEvaluationController extends Controller
{
    // Buscar las evaluaciones del estudiante
    public function findEvaluations($ci)
    {
        $student = DB::table('students')->where('ci', $ci)->first();
        if (!$student) {
            return response()->json(['message' => 'El estudiante no existe'], 404);
        }
        $id = $student->id;

        return response()->json($id);
    }

    //buscar el titulo de la evaluacion
    public function findById($id)
    {
        $evaluation = Evaluation::where('id', $id)->first();
        if (!$evaluation) {
            return response()->json(['message' => 'La evaluación no existe'], 404);
        }
        return response()->json($evaluation);
    }

    public function getQuestionsWithAnswers($id)
    {
        // Buscar al estudiante
        $student = Student::find($id);
        if (!$student) {
            return response()->json(['message' => 'El estudiante no existe'], 404);
        }

        // Obtener la prueba del estudiante
        $test = StudentTest::where('student_id', $student->id)->first();
        if (!$test) {
            return response()->json(['message' => 'Prueba no encontrada'], 404);
        }

        // Obtener evaluación
        $evaluation = Evaluation::find($test->evaluation_id);
        if (!$evaluation) {
            return response()->json(['message' => 'Evaluación no encontrada'], 404);
        }

        // 🔹 Verificar si el grupo del estudiante ya completó la evaluación
        $group = $student->groups()
            ->where('evaluation_id', $evaluation->id)
            ->first();

        if ($group && $group->status === 'completado') {
            return response()->json([
                'message'        => 'La evaluación de este grupo ya fue finalizada',
                'examCompleted'  => true,
                'student_test_id' => $test->id,
                'test_code'      => $test->code,
                'evaluation_id'  => $test->evaluation_id
            ], 200);
        }

        $timezone = 'America/La_Paz';

        if (empty($test->start_time)) {
            // Si no hay hora, usar la fecha de la evaluación
            $startTime = Carbon::parse($evaluation->date_of_realization, $timezone);
        } else {
            // Detectar si start_time contiene fecha
            if (preg_match('/^\d{2,4}-\d{2}-\d{2}/', $test->start_time)) {
                // Ya incluye fecha completa
                $startTime = Carbon::parse($test->start_time, $timezone);
            } else {
                // Solo hora, combinar con la fecha de la evaluación
                $evaluationDate = Carbon::parse($evaluation->date_of_realization, $timezone)->format('Y-m-d');
                $startTime = Carbon::parse($evaluationDate . ' ' . $test->start_time, $timezone);
            }
        }

        $now = Carbon::now($timezone);
        $durationMinutes = (int) $evaluation->time;
        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        // Calcular segundos restantes
        $remainingSeconds = $now->greaterThanOrEqualTo($endTime)
            ? 0
            : $now->diffInSeconds($endTime, false);

        // Obtener preguntas del estudiante con sus respuestas
        $studentQuestions = StudentTestQuestion::with('question.bank_answers')
            ->where('student_test_id', $test->id)
            ->orderBy('question_order')
            ->get();

        if ($studentQuestions->isEmpty()) {
            return response()->json(['message' => 'No hay preguntas asignadas a este estudiante'], 404);
        }

        $formattedQuestions = $studentQuestions->map(function ($sq) {
            return [
                'question_id'     => $sq->question->id,
                'question'        => $sq->question->question,
                'description'     => $sq->question->description,
                'image'           => $sq->question->image,
                'score_assigned'  => $sq->score_assigned,
                'student_answer'  => $sq->student_answer,
                'is_correct'      => $sq->is_correct,
                'answers'         => $sq->question->bank_answers,
            ];
        });

        return response()->json([
            'student_test_id'    => $test->id,
            'test_code'          => $test->code,
            'evaluation_id'      => $test->evaluation_id,
            'start_time'         => $startTime->toIso8601String(),
            'end_time'           => $endTime->toIso8601String(),
            'current_time'       => $now->toIso8601String(),
            'remaining_seconds'  => $remainingSeconds,
            'questions'          => $formattedQuestions,
        ]);
    }

    // funcion para obtener las preguntas con sus respuestas correctas
    public function getQuestionsWithCorrectAnswers($id)
    {
        $student = Student::find($id);
        if (!$student) {
            return response()->json(['message' => 'El estudiante no existe'], 404);
        }

        $test = StudentTest::where('student_id', $student->id)->first();
        if (!$test) {
            return response()->json(['message' => 'Prueba no encontrada'], 404);
        }

        $evaluation = Evaluation::find($test->evaluation_id);
        if (!$evaluation) {
            return response()->json(['message' => 'Evaluación no encontrada'], 404);
        }

        // Obtener todas las respuestas posibles, no solo las correctas
        $studentQuestions = StudentTestQuestion::with(['question.bank_answers'])
            ->where('student_test_id', $test->id)
            ->get();

        if ($studentQuestions->isEmpty()) {
            return response()->json(['message' => 'No hay preguntas asignadas a este estudiante'], 404);
        }

        $formattedQuestions = $studentQuestions->map(function ($sq) {       
            return [                                        
                'question_id' => $sq->question->id,
                'question' => $sq->question->question,
                'description' => $sq->question->description,
                'image' => $sq->question->image,
                'score_assigned' => $sq->score_assigned,
                'student_answer' => $sq->student_answer,
                'is_correct' => $sq->is_correct,
                'answers' => $sq->question->bank_answers,
            ];
        });

        return response()->json([
            'student_test_id' => $test->id,
            'student_id' => $test->student_id,
            'evaluation_id' => $test->evaluation_id,
            'questions' => $formattedQuestions,
        ]);
    }
}
