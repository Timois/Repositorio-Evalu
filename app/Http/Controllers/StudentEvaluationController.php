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

        // Obtener todas las evaluaciones del estudiante (programadas o finalizadas)
        $evaluations = DB::table('student_tests')
            ->join('evaluations', 'student_tests.evaluation_id', '=', 'evaluations.id')
            ->select(
                'evaluations.id as evaluation_id',
                'evaluations.title',
                'student_tests.status as status', // pendiente, en_progreso, completado
                'student_tests.score_obtained',
                'student_tests.id as student_test_id'
            )
            ->where('student_tests.student_id', $student->id)
            ->orderBy('evaluations.created_at', 'desc')
            ->get();

        if ($evaluations->isEmpty()) {
            return response()->json(['message' => 'El estudiante no tiene evaluaciones asignadas'], 404);
        }

        return response()->json($evaluations);
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
        // 1️⃣ Buscar al estudiante
        $student = Student::find($id);
        if (!$student) {
            return response()->json(['message' => 'El estudiante no existe'], 404);
        }

        // 2️⃣ Consultar todas las evaluaciones que tiene el estudiante
        $studentTests = StudentTest::where('student_id', $student->id)
            ->with('evaluation:id,title,date_of_realization,status')
            ->get();

        if ($studentTests->isEmpty()) {
            return response()->json(['message' => 'El estudiante no tiene evaluaciones registradas'], 404);
        }

        // 3️⃣ Ver cuántas evaluaciones tiene
        $evaluations = $studentTests->map(function ($test) {
            return [
                'evaluation_id'   => $test->evaluation_id,
                'evaluation_name' => $test->evaluation->title,
                'date'            => $test->evaluation->date_of_realization,
                'status'          => $test->evaluation->status,
                'test_code'       => $test->code,
                'student_test_id' => $test->id,
            ];
        })->unique('evaluation_id')->values();

        // Si tiene más de una evaluación, mostrar todas
        if ($evaluations->count() > 1) {
            return response()->json([
                'message' => 'El estudiante tiene varias evaluaciones registradas, seleccione una.',
                'evaluations' => $evaluations,
            ], 200);
        }

        // 4️⃣ Si solo tiene una evaluación, continuar automáticamente
        $test = $studentTests->first();
        $evaluation = $test->evaluation;

        if (!$evaluation) {
            return response()->json(['message' => 'Evaluación no encontrada'], 404);
        }

        // 5️⃣ Verificar si el grupo ya completó la evaluación
        $group = $student->groups()
            ->where('evaluation_id', $evaluation->id)
            ->first();

        if ($group && $group->status === 'completado') {
            return response()->json([
                'message'         => 'La evaluación de este grupo ya fue finalizada',
                'examCompleted'   => true,
                'student_test_id' => $test->id,
                'test_code'       => $test->code,
                'evaluation_id'   => $test->evaluation_id
            ], 200);
        }

        // 6️⃣ Calcular tiempos y preguntas
        $timezone = 'America/La_Paz';
        $startTime = empty($test->start_time)
            ? Carbon::parse($evaluation->date_of_realization, $timezone)
            : (preg_match('/^\d{2,4}-\d{2}-\d{2}/', $test->start_time)
                ? Carbon::parse($test->start_time, $timezone)
                : Carbon::parse(Carbon::parse($evaluation->date_of_realization)->format('Y-m-d') . ' ' . $test->start_time, $timezone));

        $now = Carbon::now($timezone);
        $durationMinutes = (int) $evaluation->time;
        $endTime = $startTime->copy()->addMinutes($durationMinutes);
        $remainingSeconds = $now->greaterThanOrEqualTo($endTime)
            ? 0
            : $now->diffInSeconds($endTime, false);

        // 7️⃣ Obtener las preguntas con respuestas
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

        // 8️⃣ Retornar la prueba con todo el contenido
        return response()->json([
            'student_test_id'    => $test->id,
            'test_code'          => $test->code,
            'evaluation_id'      => $test->evaluation_id,
            'evaluation_name'    => $evaluation->name,
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
