<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationAssignQuestion;
use App\Http\Requests\ValidationEvaluation;
use App\Models\Areas;
use App\Models\Evaluation;
use App\Models\QuestionBank;
use App\Models\QuestionEvaluation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationController extends Controller
{
    public function find()
    {
        $evaluation = Evaluation::orderBy('id', 'ASC')->get();
        return response()->json($evaluation);
    }

    public function create(ValidationEvaluation $request)
    {
        $evaluation = new Evaluation();
        $evaluation->title = $request->title;
        $evaluation->description = $request->description;
        $evaluation->total_score = $request->total_score;
        $evaluation->is_random = $request->is_random;
        $evaluation->status = $request->status;
        $evaluation->type = $request->type;
        $evaluation->academic_management_period_id = $request->academic_management_period_id;
        $evaluation->save();
        return response()->json($evaluation);
    }

    public function findAndUpdate(ValidationEvaluation $request, string $id)
    {
        try {
            // Busca la evaluación por su ID
            $evaluation = Evaluation::findOrFail($id);

            // Captura los datos del request, solo los campos editables
            $updateData = $request->only([
                'title',
                'description',
                'total_score',
                'is_random',
                'status',
                'type'
            ]);

            // Convierte 'title' a mayúsculas si está presente
            if (isset($updateData['title'])) {
                $updateData['title'] = strtoupper($updateData['title']);
            }

            // Convierte 'description' a minúsculas si está presente
            if (isset($updateData['description'])) {
                $updateData['description'] = strtolower($updateData['description']);
            }

            // Actualiza los datos en el modelo
            $evaluation->update($updateData);

            // Retorna la respuesta exitosa
            return response()->json([
                'message' => 'Evaluación actualizada exitosamente',
                'data' => $evaluation
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => "La evaluación con id: $id no existe."
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la evaluación',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function findById(string $id)
    {
        $evaluation = Evaluation::find($id);
        if (!$evaluation)
            return ["message:", "La evaluación con el id:" . $id . " no existe."];
        return response()->json($evaluation);
    }


    // Función para calcular la nota total por área
    public function ListAssignedQuestions(Request $request)
    {
        $questions = QuestionEvaluation::get();
        return response()->json($questions);
    }
}
