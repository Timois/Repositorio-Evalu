<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationEvaluation;
use App\Models\AcademicManagementCareer;
use App\Models\AcademicManagementPeriod;
use App\Models\Evaluation;
use App\Models\QuestionEvaluation;
use Illuminate\Http\Request;

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
        $evaluation = Evaluation::find($id);
        if (!$evaluation) {
            return response()->json(["message" => "La evaluación con el id: " . $id . " no existe."], 404);
        }
        if ($request->has('title')) {
            $evaluation->title = $request->title;
        }
        if ($request->has('description')) {
            $evaluation->description = $request->description;
        }
        if ($request->has('passing_score')) {
            $evaluation->passing_score = $request->passing_score;
        }
        if ($request->has('date_of_realization')) {
            $evaluation->date_of_realization = $request->date_of_realization;
        }
        if ($request->has('time')) {
            $evaluation->time = $request->time;
        }
        if ($request->has('status')) {
            $evaluation->status = $request->status;
        }
        if ($request->has('type')) {
            $evaluation->type = $request->type;
        }
        if ($request->has('total_score')) {
            $evaluation->total_score = $request->total_score;
        }
        $evaluation->save();
        return response()->json($evaluation);
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

    // Función para obtener las evaluaciones por periodo
    public function findEvaluationsByPeriod(string $periodId)
    {
        $evaluations = Evaluation::where('academic_management_period_id', $periodId)->get();

        if ($evaluations->isEmpty()) {
            return response()->json(["message" => "No se encontraron evaluaciones para el periodo con ID: $periodId"], 404);
        }

        return response()->json($evaluations);
    }
}
