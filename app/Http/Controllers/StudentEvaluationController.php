<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\Group;
use App\Models\Student;
use App\Models\StudentTest;
use App\Models\StudentTestQuestion;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        $evaluations = DB::table('group_student')
            ->join('groups', 'group_student.group_id', '=', 'groups.id')
            ->join('evaluations', 'groups.evaluation_id', '=', 'evaluations.id')
            ->join('academic_management_period', 'evaluations.academic_management_period_id', '=', 'academic_management_period.id')
            ->join('periods', 'academic_management_period.period_id', '=', 'periods.id')
            ->join('academic_management_career', 'academic_management_period.academic_management_career_id', '=', 'academic_management_career.id')
            ->join('careers', 'academic_management_career.career_id', '=', 'careers.id')
            ->join('academic_management', 'academic_management_career.academic_management_id', '=', 'academic_management.id')
            ->leftJoin('student_tests', function ($join) use ($student) {
                $join->on('student_tests.evaluation_id', '=', 'evaluations.id')
                    ->where('student_tests.student_id', '=', $student->id);
            })
            ->select(
                'evaluations.id as evaluation_id',
                'evaluations.status as evaluation_status',
                'evaluations.title as evaluation_title',
                'groups.id as group_id',
                'groups.name as group_name',
                'groups.status as group_status',
                'careers.name as career_name',
                'periods.period as period_name',
                'periods.level as period_level',
                'academic_management.year as gestion_year',
                'student_tests.id as student_test_id',
                'student_tests.score_obtained',
                DB::raw($student->id . ' as student_id')
            )
            ->where('group_student.student_id', $student->id)
            ->groupBy(
                'evaluations.id',
                'evaluations.title',
                'evaluations.status',
                'groups.id',
                'groups.name',
                'groups.status',
                'careers.name',
                'periods.period',
                'periods.level',
                'academic_management.year',
                'student_tests.id',
                'student_tests.score_obtained'
            )
            ->orderBy('evaluations.created_at', 'asc')
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

    public function getQuestionsByStudentAndEvaluation($studentId, $evaluationId)
    {
        // 1️⃣ Buscar al estudiante
        $student = Student::find($studentId);
        if (!$student) {
            return response()->json(['message' => 'El estudiante no existe'], 404);
        }

        // 2️⃣ Buscar la evaluación
        $evaluation = Evaluation::find($evaluationId);
        if (!$evaluation) {
            return response()->json(['message' => 'La evaluación no existe'], 404);
        }

        // 3️⃣ Buscar la prueba (student_test) asignada al estudiante para esa evaluación
        $test = StudentTest::where('student_id', $student->id)
            ->where('evaluation_id', $evaluation->id)
            ->first();

        if (!$test) {
            return response()->json(['message' => 'No se encontró una prueba asignada a este estudiante para la evaluación seleccionada'], 404);
        }

        // 4️⃣ Calcular tiempos
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

        // 5️⃣ Obtener las preguntas con respuestas
        $studentQuestions = StudentTestQuestion::with('question.bank_answers')
            ->where('student_test_id', $test->id)
            ->orderBy('question_order')
            ->get();

        if ($studentQuestions->isEmpty()) {
            return response()->json(['message' => 'No hay preguntas asignadas para esta prueba'], 404);
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

        // 6️⃣ Retornar toda la información
        return response()->json([
            'student_test_id'    => $test->id,
            'test_code'          => $test->code,
            'evaluation_id'      => $test->evaluation_id,
            'evaluation_title'    => $evaluation->title,
            'start_time'         => $startTime->toIso8601String(),
            'end_time'           => $endTime->toIso8601String(),
            'current_time'       => $now->toIso8601String(),
            'remaining_seconds'  => $remainingSeconds,
            'questions'          => $formattedQuestions,
        ]);
    }

    // funcion para obtener las preguntas con sus respuestas correctas
    public function getQuestionsWithCorrectAnswers($id, Request $request)
    {
        $student = Student::find($id);
        if (!$student) {
            return response()->json(['message' => 'El estudiante no existe'], 404);
        }

        // Recibir evaluation_id desde la solicitud
        $evaluationId = $request->evaluation_id;

        // Buscar la prueba correcta
        $test = StudentTest::where('student_id', $student->id)
            ->where('evaluation_id', $evaluationId)
            ->first();

        if (!$test) {
            return response()->json(['message' => 'Prueba no encontrada para esta evaluación'], 404);
        }

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
