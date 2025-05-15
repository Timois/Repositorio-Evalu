<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\QuestionBank;
use App\Models\Student;
use App\Models\StudentTest;
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
        $student = Student::where('id', $id)->first();
        if (!$student) {
            return response()->json(['message' => 'El estudiante no existe'], 404);
        }
        $test = StudentTest::where('student_id', $student->id)->first();
        
        if (!$test)
            return response()->json(['message' => 'Prueba no encontrada'], 404);

        $orderedQuestionIds = json_decode($test->questions_order, true); // Asegura que sea array

        if (!is_array($orderedQuestionIds)) {
            return response()->json(['message' => 'Formato inválido de preguntas ordenadas'], 400);
        }

        $questions = QuestionBank::with('bank_answers')
            ->whereIn('id', $orderedQuestionIds)
            ->get()
            ->keyBy('id');

        $orderedQuestions = [];
        foreach ($orderedQuestionIds as $qid) {
            if (isset($questions[$qid])) {
                $orderedQuestions[] = $questions[$qid];
            }
        }

        return response()->json([
            'student_test_id' => $test->id,
            'student_id' => $test->student_id,
            'evaluation_id' => $test->evaluation_id,
            'questions' => $orderedQuestions
        ]);
    }
}
