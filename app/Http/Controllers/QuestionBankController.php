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
        // Manejar la imagen si está presente
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/questions'), $imageName);
            $imagePath = asset('images/questions/' . $imageName);
        }

        $question = new QuestionBank();
        $question->question = $request->question;
        $question->description = $request->description;
        $question->image = $imagePath;
        // $question->total_weight = $request->total_weight;
        $question->type = $request->type;
        $question->status = $request->status;
        $question->area_id = $request->area_id;
        $question->excel_import_id = $request->excel_import_id;
        $question->save();
        return $question;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function findAndUpdate(ValidationQuestionBank $request, string $id)
    {
        try {
            $question = QuestionBank::findOrFail($id);

            // Update fields if they exist in the request
            $updateData = $request->only([
                'question',
                'description',
                'total_weight',
                'type',
                'status'
            ]);

            // Convert question to lowercase if it exists
            if (isset($updateData['question'])) {
                $updateData['question'] = strtolower($updateData['question']);
            }

            // Handle image upload
            $image = $request->file('image');
            if ($image) {
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $imagePath = asset('images/questions/' . $imageName);
                $image->move(public_path('images/questions'), $imageName);
                $updateData['image'] = $imagePath;
            }

            // Update the question
            $question->update($updateData);

            return response()->json([
                'message' => 'Pregunta actualizada exitosamente',
                'data' => $question
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => "La pregunta con id: $id no existe."
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la pregunta',
                'error' => $e->getMessage()
            ], 500);
        }
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
