<?php

namespace App\Http\Controllers;

use App\Models\Result;
use Illuminate\Http\Request;

class ResultsController extends Controller
{
    public function find()
    {
        $results = Result::orderBy('id', 'asc')->get();
        return response()->json($results);
    }
    public function findById(string $id)
    {
        $result = Result::find($id);
        if ($result) {
            return response()->json($result);
        } else {
            return response()->json(['message' => 'No se encontro el resultado'], 404);
        }
    }

    public function create(Request $request)
    {
        $request->validate([
            'student_test_id' => 'required|integer',
            'qualification' => 'required|numeric',
            'maximum_score' => 'required|numeric',
            'minimum_score' => 'required|numeric',
            'exam_duration' => 'required|integer',
            'status' => 'enum:admitido,no_admitido',
        ]);

        $result = Result::create($request->all());
        return response()->json($result, 201);
    }
}
