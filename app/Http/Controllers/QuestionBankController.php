<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationQuestionBank;
use App\Models\QuestionBank;
use Illuminate\Http\Request;
use App\Http\Requests\ValidationsQuestionBank;
use PhpOffice\PhpSpreadsheet\Worksheet\Validations;

class QuestionBankController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function find()
    {
        $questions = QuestionBank::orderBy('id', 'ASC')->get();
        return response()->json($questions);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ValidationQuestionBank $request)
    {
        $image = $request->file('image');
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $imagePath = asset('images/questions/' . $imageName);
        $image->move(public_path('images/questions'), $imageName);

        $question = new QuestionBank();
        $question->question = $request->question;
        $question->description = $request->description;
        $question->image = $imagePath;
        $question->total_weight = $request->total_weight;
        $question->type = $request->type;
        $question->status = $request->status;
        $question->save();
        return $question;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function findAndUpdate(ValidationQuestionBank $request, string $id)
    {
        $question = QuestionBank::find($id);

        if(!$question)
            return ["message:", "La pregunta con id:" . $id . " no existe."];
        if($request->question)
            $question->question = strtolower($request->question);
        if($request->description)
            $question->description = $request->description;
        if($request->total_weight)
            $question->total_weight = $request->total_weight;
        if($request->type)
            $question->type = $request->type;
        if($request->status)
            $question->status = $request->status;

        $image = $request->file('image');
        if(!$image){
            $question->save();
            return $question;
        }
        
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $imagePath = asset('images/questions/' . $imageName);
        $image->move(public_path('images/questions'), $imageName);
        $question->image = $imagePath;
        
        $question->save();
        return $question;
    }

    /**
     * Display the specified resource.
     */
    public function findById(string $id)
    {
        $question = QuestionBank::find($id);
        if (!$question)
            return ["message:", "La pregunta con id:" . $id . " no existe."];
        return response()->json($question);
    }

    
    public function remove(string $id)
    {
        $question = QuestionBank::find($id);
        if (!$question)
            return ["message:", "La pregunta con id:" . $id . " no existe."];
        $question->status = 'inactivo';
        $question->save();
        return $question;
    }
}
