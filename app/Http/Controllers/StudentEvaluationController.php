<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\Group;
use App\Models\QuestionBank;
use App\Models\Student;
use App\Models\StudentTest;
use App\Models\StudentTestQuestion;
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

        // Obtener la evaluación asociada
        $evaluation = Evaluation::find($test->evaluation_id);
        if (!$evaluation) {
            return response()->json(['message' => 'Evaluación no encontrada'], 404);
        }

        // Obtener el grupo del estudiante
        $group = $student->groups()->where('evaluation_id', $evaluation->id)->first();
        if (!$group) {
            return response()->json(['message' => 'El estudiante no está asignado a un grupo para esta evaluación'], 403);
        }

        // Obtener la configuración de horario del grupo para la evaluación
        $groupEvaluation = Group::find($group->id);
        
        // Validar fecha y hora
        $currentDateTime = now(); // Fecha y hora actual
        $startDateTime = \Carbon\Carbon::parse($groupEvaluation->start_time);
        $endDateTime = \Carbon\Carbon::parse($groupEvaluation->end_time);

        // Verificar si la evaluación está dentro del horario permitido
        if ($currentDateTime->lt($startDateTime)) {
            return response()->json(['message' => 'La evaluación aún no está disponible'], 403);
        }
        if ($currentDateTime->gt($endDateTime)) {
            return response()->json(['message' => 'El tiempo para la evaluación ha expirado'], 403);
        }

        // Obtener preguntas asignadas al estudiante con sus respuestas posibles
        $studentQuestions = StudentTestQuestion::with('question.bank_answers')
            ->where('student_test_id', $test->id)
            ->orderBy('question_order')
            ->get();

        if ($studentQuestions->isEmpty()) {
            return response()->json(['message' => 'No hay preguntas asignadas a este estudiante'], 404);
        }

        // Formatear salida
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
