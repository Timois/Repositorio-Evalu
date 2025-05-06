<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationStudentTest;
use App\Models\StudentTest;
use Illuminate\Http\Request;

class StudenTestsController extends Controller
{
    public function find()
    {
        $test = StudentTest::orderBy('id','asc')->get();
        return response()->json($test);
    }

    public function findById(string $id)
    {
        $test = StudentTest::find($id);
        if (!$test)
            return ["message:", "La prueba con id:" . $id . " no existe."];
        return response()->json($test);
    }

    
    public function create(ValidationStudentTest $request){
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

    public function findAndUpdate(ValidationStudentTest $request, string $id){
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

}
