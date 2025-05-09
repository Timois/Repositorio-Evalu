<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationStudentTest;
use App\Models\Evaluation;
use App\Models\QuestionBank;
use App\Models\QuestionEvaluation;
use App\Models\Student;
use App\Models\StudentTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudenTestsController extends Controller
{
    public function find()
    {
        $test = StudentTest::orderBy('id', 'asc')->get();
        return response()->json($test);
    }

    public function findById(string $id)
    {
        $test = StudentTest::find($id);
        if (!$test)
            return ["message:", "La prueba con id:" . $id . " no existe."];
        return response()->json($test);
    }


    public function create(ValidationStudentTest $request)
    {
        $test = new StudentTest();
        $test->evaluation_id = $request->evaluation_id;
        $test->student_id = $request->student_id;
        $test->code = $request->code;
        $test->start_time = $request->start_time;
        $test->end_time = $request->end_time;
        $test->score_obtained = $request->score_obtained;
        $test->correct_answers = $request->correct_answers;
        $test->incorrect_answers = $request->incorrect_answers;
        $test->not_answered = $request->not_answered;
        $test->status = $request->status;
        $test->save();
        return response()->json($test);
    }

    public function findAndUpdate(ValidationStudentTest $request, string $id)
    {
        $test = StudentTest::find($id);
        if (!$test)
            return ["message:", "La prueba con id:" . $id . " no existe."];
        if ($request->name)
            $test->name = $request->name;
        if ($request->code)
            $test->code = $request->code;
        if ($request->start_time)
            $test->start_time = $request->start_time;
        if ($request->end_time)
            $test->end_time = $request->end_time;
        if ($request->score_obtained)
            $test->score_obtained = $request->score_obtained;
        if ($request->correct_answers)
            $test->correct_answers = $request->correct_answers;
        if ($request->incorrect_answers)
            $test->incorrect_answers = $request->incorrect_answers;
        if ($request->not_answered)
            $test->not_answered = $request->not_answered;
        if ($request->status)
            $test->status = $request->status;
        $test->save();
        return response()->json($test);
    }

    public function assignRandomEvaluation(Request $request)
    {
        $evaluation = Evaluation::find($request->evaluation_id);
        if (!$evaluation)
            return response()->json(["message" => "La evaluación con id: {$request->evaluation_id} no existe."], 404);

        $questions = QuestionEvaluation::where('evaluation_id', $evaluation->id)->pluck('question_id')->toArray();
        if (count($questions) === 0)
            return response()->json(["message" => "La evaluación no tiene preguntas."], 400);

        // Obtener IDs de estudiantes que ya tienen asignada esta evaluación
        $assignedStudentIds = StudentTest::where('evaluation_id', $evaluation->id)
            ->pluck('student_id')
            ->toArray();

        // Obtener estudiantes que NO tienen asignada esta evaluación
        $availableStudents = Student::whereNotIn('id', $assignedStudentIds)
            ->inRandomOrder()
            ->limit($evaluation->qualified_students)
            ->get();

        if ($availableStudents->count() < $evaluation->qualified_students)
            return response()->json([
                "message" => "No hay suficientes estudiantes disponibles que no hayan sido asignados a esta evaluación.",
                "available" => $availableStudents->count(),
                "required" => $evaluation->qualified_students
            ], 400);

        $assignedCount = 0;
        foreach ($availableStudents as $student) {
            shuffle($questions); // Mezcla aleatoriamente las preguntas
            $test = new StudentTest();
            $test->evaluation_id = $evaluation->id;
            $test->student_id = $student->id;
            $test->code = Str::uuid(); // O cualquier identificador
            $test->start_time = null;
            $test->end_time = null;
            $test->score_obtained = 0;
            $test->correct_answers = 0;
            $test->incorrect_answers = 0;
            $test->not_answered = count($questions);
            $test->status = 'evaluado';
            $test->questions_order = json_encode($questions); // Guarda el orden aleatorio
            $test->save();

            $assignedCount++;
        }

        return response()->json([
            "message" => "Evaluación asignada a {$assignedCount} estudiantes con orden aleatorio de preguntas."
        ]);
    }
        public function getQuestionsWithAnswers($student_test_id)
        {
            $test = StudentTest::find($student_test_id);
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

    public function getStudentsByEvaluation($evaluationId)
    {
        $students = StudentTest::with('student') // Asegúrate de tener la relación definida
            ->where('evaluation_id', $evaluationId)
            ->get();

        return response()->json($students);
    }
}
