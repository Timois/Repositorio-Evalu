<?php

namespace App\Http\Controllers;

use App\Models\BackupAnswerTest;
use Illuminate\Http\Request;

class BackupAnswerTestController extends Controller
{
    public function find()
    {
        $backup = BackupAnswerTest::orderBy('id', 'asc')->get();
        return response()->json($backup);
    }

    public function create (Request $request)
    {
        $backup = new BackupAnswerTest();
        $backup->student_test_id = $request->student_test_id;
        $backup->question_id = $request->question_id;
        $backup->answer_id = $request->answer_id;
        $backup->time = $request->time;
        $backup->save();
        return response()->json($backup);
    }
    public function findById(string $id)
    {
        $backup = BackupAnswerTest::find($id);
        if (!$backup)
            return ["message:", "La respuesta con id:" . $id . " no existe."];
        return response()->json($backup);
    }
    
    public function findAndUpdate(Request $request, string $id)
    {
        $backup = BackupAnswerTest::find($id);
        if (!$backup)
            return ["message:", "La respuesta con id:" . $id . " no existe."];
        if ($request->student_test_id)
            $backup->student_test_id = $request->student_test_id;
        if ($request->question_id)
            $backup->question_id = $request->question_id;
        if ($request->answer_id)
            $backup->answer_id = $request->answer_id;
        if ($request->time)
            $backup->time = $request->time;
        $backup->save();
        return response()->json($backup);
    }
}
