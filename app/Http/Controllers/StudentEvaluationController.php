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

        // Validar si la evaluación ya fue iniciada
        if (!$test->start_time) {
            return response()->json(['message' => 'La evaluación aún no ha sido iniciada'], 409);
        }

        $timezone = 'America/La_Paz';

        // Convertir start_time a DateTime en zona correcta
        if (strlen($test->start_time) <= 8) { // Solo hora
            $evaluationDate = Carbon::parse($evaluation->date_of_realization, $timezone)->format('Y-m-d');
            $startTime = Carbon::createFromFormat('Y-m-d H:i:s', $evaluationDate . ' ' . $test->start_time, $timezone);
        } else {
            $startTime = Carbon::parse($test->start_time)->timezone($timezone);
        }

        $now = Carbon::now($timezone);
        $durationMinutes = (int) $evaluation->time;

        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        if ($now->greaterThanOrEqualTo($endTime)) {
            $remainingSeconds = 0;
        } else {
            $remainingSeconds = $now->diffInSeconds($endTime, false);
        }

        // Obtener preguntas
        $studentQuestions = StudentTestQuestion::with('question.bank_answers')
            ->where('student_test_id', $test->id)
            ->orderBy('question_order')
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
            'test_code' => $test->code,
            'evaluation_id' => $test->evaluation_id,
            'start_time' => $startTime->toIso8601String(), // Fecha y hora completas
            'end_time' => $endTime->toIso8601String(),     // Fin exacto
            'current_time' => now($timezone)->toIso8601String(), // Hora actual del servidor
            'questions' => $formattedQuestions,
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
            ->orderBy('question_order')
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
