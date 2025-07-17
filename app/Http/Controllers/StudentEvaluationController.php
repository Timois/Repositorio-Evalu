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

    public function startEvaluation($evaluationId)
    {
        $evaluation = Evaluation::find($evaluationId);
        if (!$evaluation) {
            return response()->json(['message' => 'Evaluación no encontrada'], 404);
        }

        // Solo asignar start_time si aún no ha sido iniciado
        $updated = StudentTest::where('evaluation_id', $evaluationId)
            ->whereNull('start_time')
            ->update(['start_time' => now()]);

        return response()->json([
            'message' => $updated ? 'Evaluación iniciada' : 'Ya se había iniciado la evaluación',
            'updated_records' => $updated,
            'start_time' => now()
        ]);
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
            return response()->json(['message' => 'La evaluación aún no ha sido iniciada'], 403);
        }

        // Calcular el tiempo restante
        $startTime = Carbon::parse($test->start_time);
        $now = Carbon::now();
        $duration = $evaluation->time; // en minutos
        $endTime = $startTime->copy()->addMinutes($duration);

        $remainingSeconds = $now->greaterThanOrEqualTo($endTime)
            ? 0
            : $endTime->diffInSeconds($now);

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
            'start_time' => $test->start_time,
            'remaining_time_seconds' => $remainingSeconds,
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
