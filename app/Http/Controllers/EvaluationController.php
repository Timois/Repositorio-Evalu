<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationAssignQuestion;
use App\Http\Requests\ValidationEvaluation;
use App\Models\AcademicManagementCareer;
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
        $evaluation->total_score = 100;
        $evaluation->passing_score = $request->passing_score;
        $evaluation->date_of_realization = $request->date_of_realization;
        $evaluation->status = $request->status;
        $evaluation->type = $request->type;
        $evaluation->time = $request->time;
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
                'passing_score', // Se agrega porque estaba en create()
                'date_of_realization', // Se agrega porque estaba en create()
                'qualified_students',
                'status',
                'type',
                'time',
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

    public function findEvaluationsByCareer(string $careerId)
    {
        // Buscar la gestión académica de la carrera
        $managementCareer = AcademicManagementCareer::where('career_id', $careerId)->first();

        if (!$managementCareer) {
            return response()->json([
                "message" => "No se encontró una gestión académica para la carrera con ID: $careerId"
            ], 404);
        }

        // Obtener los periodos de esa gestión académica con sus evaluaciones
        $periods = AcademicManagementPeriod::with('evaluations')
            ->where('academic_management_career_id', $managementCareer->id)
            ->get();

        // Si no hay periodos, retornar mensaje
        if ($periods->isEmpty()) {
            return response()->json([
                "message" => "No se encontraron periodos para la carrera con ID: $careerId"
            ], 404);
        }

        // Agrupar evaluaciones por periodo
        $result = $periods->map(function ($period) {
            return [
                'period_id' => $period->id,
                'period_name' => $period->period,
                'evaluations' => $period->evaluations
            ];
        });

        return response()->json($result);
    }
}
