<?php

namespace App\Http\Controllers;

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

    public function create(Request $request){
        $test = new StudentTest();
        $test->evaluation_id = $request->evaluation_id;
        $test->name = $request->name;
        $test->code = $request->code;
        $test->range_time = $request->range_time;
        $test->minimum_score = $request->minimum_score;
        $test->status = $request->status;
        $test->save();
        return $test;
    }

    public function findAndUpdate(Request $request, string $id){
        $test = StudentTest::find($id);
        if (!$test)
            return ["message:", "La prueba con id:" . $id . " no existe."];
        if ($request->name)
            $test->name = $request->name;
        if ($request->code)
            $test->code = $request->code;
        if ($request->range_time)
            $test->range_time = $request->range_time;
        if ($request->minimum_score)
            $test->minimum_score = $request->minimum_score;
        if ($request->status)
            $test->status = $request->status;
        $test->save();
        return $test;
    }
}
