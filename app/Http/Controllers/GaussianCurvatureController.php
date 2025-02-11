<?php

namespace App\Http\Controllers;

use App\Models\GaussianCurvature;
use Illuminate\Http\Request;

class GaussianCurvatureController extends Controller
{
    public function find(){
        $test = GaussianCurvature::orderBy('id','asc')->get();
        return response()->json($test);
    }

    public function findById(string $id){
        $test = GaussianCurvature::find($id);
        if (!$test)
            return ["message:", "La prueba con id:" . $id . " no existe."];
        return response()->json($test);
    }

    public function create(Request $request){
        $test = new GaussianCurvature();
        $test->name = $request->name;
        $test->code = $request->code;
        $test->range_time = $request->range_time;
        $test->minimum_score = $request->minimum_score;
        $test->status = $request->status;
        $test->save();
        return $test;
    }

    public function findAndUpdate(Request $request, string $id){
        $test = GaussianCurvature::find($id);
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

    public function unsubscribe(string $id){
        $test = GaussianCurvature::find($id);
        if (!$test)
            return ["message:", "La prueba con id:" . $id . " no existe."];
        $test->status = "inactivo";
        $test->save();
        return $test;
    }
}
