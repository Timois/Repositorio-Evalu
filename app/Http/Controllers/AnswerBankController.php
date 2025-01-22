<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationAnswerBank;
use App\Models\AnswerBank;
use Illuminate\Http\Request;

class AnswerBankController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function find()
    {
        $answers = AnswerBank::orderBy('id', 'ASC')->get();
        return response()->json($answers);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ValidationAnswerBank $request)
    {
        $answer = new AnswerBank();
        $answer->answer = $request->answer;
        $answer->weight = $request->weight;
        $answer->is_correct = $request->is_correct;
        $answer->status = $request->status;
        $answer->bank_question_id = $request->bank_question_id;
        $answer->save();
        return $answer;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function findAndUpdate(ValidationAnswerBank $request, string $id)
    {
        $answer = AnswerBank::find($id);
        if(!$answer)
            return ["message:", "La respuesta con el id:". $id . " no existe."];
        if ($request->answer)
            $answer->answer = $request->answer;
        if ($request->weight)
            $answer->weight = $request->weight;
        if ($request->is_correct)
            $answer->is_correct = $request->is_correct;
        if ($request->status)
            $answer->status = $request->status;
        $answer->save();
        return $answer;
    }

    /**
     * Display the specified resource.
     */
    public function findById(string $id)
    {
        $answer = AnswerBank::find($id);
        if (!$answer)
            return ["message:", "La respuesta con id:" . $id . " no existe."];
        return response()->json($answer);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function remove(string $id)
    {
        $answer = AnswerBank::find($id);
        if (!$answer)
            return ["message:", "La respuesta con id:" . $id . " no existe."];
        $answer->status = 'inactivo';
        $answer->save();
        return $answer;
    }
}
