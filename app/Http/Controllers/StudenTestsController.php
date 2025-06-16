<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationStudentTest;
use App\Models\Student;
use App\Models\StudentTest;

class StudenTestsController extends Controller
{
    public function find($evaluationId)
    {
        $students = StudentTest::with('student')
            ->where('evaluation_id', $evaluationId)
            ->orderBy('id', 'asc')
            ->get();

        // Retornamos solo los datos del estudiante
        $studentData = $students->pluck('student');

        return response()->json($studentData);
    }

    public function findById(string $id)
    {
        $test = StudentTest::find($id);
        if (!$test)
            return ["message:", "La prueba con id:" . $id . " no existe."];
        return response()->json($test);
    }
    public function findIdByCi(string $ci)
    {
        $id = Student::where('ci', $ci)->first();
        if (!$id)
            return ["message:", "El estudiante con ci:" . $ci . " no existe."];
        return response()->json($id);
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


    public function getStudentsByEvaluation($evaluationId)
    {
        $students = StudentTest::with('student') // Asegúrate de tener la relación definida
            ->where('evaluation_id', $evaluationId)
            ->get();

        return response()->json($students);
    }
}
