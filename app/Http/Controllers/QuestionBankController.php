<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationQuestionBank;
use App\Models\QuestionBank;
use App\Models\ExcelImports;

use function Pest\Laravel\json;

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
            $imagePath = public_path('images' . DIRECTORY_SEPARATOR . 'units' . DIRECTORY_SEPARATOR . $request->area_id . DIRECTORY_SEPARATOR . 'Questions' . DIRECTORY_SEPARATOR . $imageName);
        }

        // Obtener el ID de importación manual o el proporcionado
        $excelImportId = $request->excel_import_id;
        if (!$excelImportId) {
            // Obtener o crear el registro para importaciones manuales
            $excelImportId = $this->setupManualImport();
        }

        $question = new QuestionBank();
        $question->question = $request->question;
        $question->description = $request->description;
        $question->question_type = $request->question_type;
        $question->image = $imagePath;
        $question->type = $request->type;
        $question->status = $request->status;
        $question->area_id = $request->area_id;
        $question->excel_import_id = $excelImportId;
        $question->save();

        return $question;
    }

    private function setupManualImport()
    {
        // Verifica si ya existe un registro manual
        $manualImport = ExcelImports::where('file_name', 'manual_creation')->first();

        if (!$manualImport) {
            // Crear el registro especial para creaciones manuales
            $manualImport = new ExcelImports();
            $manualImport->file_name = 'manual_creation';
            $manualImport->size = 0;
            $manualImport->status = 'completado';
            $manualImport->file_path = 'none'; // No hay archivo asociado
            $manualImport->save();
        }

        return $manualImport->id;
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
                'difficulty',
                'question_type',
                'type'
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
    public function findByIdArea(string $id)
    {
        $question = QuestionBank::where('area_id', $id)->get();
        if (!$question)
            return ["message:", "La pregunta con id:" . $id . " no existe."];

        // sacar la cantidad de total de preguntas por area
        $totalQuestions = QuestionBank::where('area_id', $id)->count();
        $question = [
            'questions' => $question,
            'totalQuestions' => $totalQuestions
        ];

        return response()->json($question);
    }

}
