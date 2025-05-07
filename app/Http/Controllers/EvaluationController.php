<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationAssignQuestion;
use App\Http\Requests\ValidationEvaluation;
use App\Models\AcademicManagementPeriod;
use App\Models\Evaluation;
use App\Models\QuestionEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
        $evaluation->passing_score = $request->passing_score;
        $evaluation->date_of_realization = $request->date_of_realization;
        $evaluation->code = Str::uuid(); // Genera un UUID automáticamente
        $evaluation->status = $request->status;
        $evaluation->type = $request->type;
        $evaluation->qualified_students = $request->qualified_students;
        $evaluation->academic_management_period_id = $request->academic_management_period_id;
        $evaluation->save();

        return response()->json($evaluation);
    }


    public function findAndUpdate(ValidationEvaluation $request, string $id)
    {
        try {
            // Busca la evaluación por su ID
            $evaluation = Evaluation::findOrFail($id);

            // Captura los datos editables del request
            $updateData = $request->only([
                'title',
                'description',
                'total_score',
                'passing_score', // Se agrega porque estaba en create()
                'date_of_realization', // Se agrega porque estaba en create()
                'qualified_students',
                'status',
                'type',
                'academic_management_period_id'
            ]);

            // Convierte 'title' a mayúsculas si está presente
            if (isset($updateData['title'])) {
                $updateData['title'] = Str::upper($updateData['title']);
            }

            // Convierte 'description' a minúsculas si está presente
            if (isset($updateData['description'])) {
                $updateData['description'] = Str::lower($updateData['description']);
            }

            // Evitar que se actualice el UUID `code`
            if (isset($updateData['code'])) {
                unset($updateData['code']);
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

    public function findPeriodById(string $id)
    {
        $evaluation = AcademicManagementPeriod::with('period')->where('id', $id)->first();

        if (!$evaluation) {
            return response()->json(["message" => "La evaluación con el id: " . $id . " no existe."], 404);
        }

        return response()->json($evaluation);   
    }
}
