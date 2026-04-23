<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationEvaluation;
use App\Models\AcademicManagementCareer;
use App\Models\AcademicManagementPeriod;
use App\Models\Evaluation;

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
        $evaluation->places = $request->places;
        $evaluation->type = $request->type;
        $evaluation->time = $request->time;
        $evaluation->status = 'activo';
        $evaluation->view_score = $request->view_score;
        $evaluation->academic_management_period_id = $request->academic_management_period_id;
        $evaluation->save();
        return response()->json($evaluation);
    }


    public function findAndUpdate(ValidationEvaluation $request, string $id)
    {
        $evaluation = Evaluation::find($id);

        if (!$evaluation) {
            return response()->json([
                "message" => "La evaluación con el id: {$id} no existe."
            ], 404);
        }

        $evaluation->update($request->validated());

        return response()->json($evaluation);
    }

    public function findById(string $id)
    {
        $evaluation = Evaluation::find($id);
        if (!$evaluation)
            return ["message:", "La evaluación con el id:" . $id . " no existe."];
        return response()->json($evaluation);
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
        // Buscar la gestión académica de la carrera y cargar la relación con AcademicManagement
        $managementCareer = AcademicManagementCareer::with('academicManagement')
            ->where('career_id', $careerId)
            ->first();

        if (!$managementCareer) {
            return response()->json([
                "message" => "No se encontró una gestión académica para la carrera con ID: $careerId"
            ], 404);
        }

        // Obtener los periodos de esa gestión académica con sus evaluaciones ordenadas por id asc
        $periods = AcademicManagementPeriod::with(['evaluations' => function ($query) {
            $query->orderBy('id', 'asc'); // 👈 aquí se ordenan las evaluaciones
        }])
            ->where('academic_management_career_id', $managementCareer->id)
            ->get();

        if ($periods->isEmpty()) {
            return response()->json([
                "message" => "No se encontraron periodos para la carrera con ID: $careerId"
            ], 404);
        }

        // Agrupar evaluaciones por periodo
        $result = $periods->map(function ($period) use ($managementCareer) {
            return [
                'year' => $managementCareer->academicManagement->year,
                'academic_management_period_id' => $period->id,
                'periodo' => $period->period,
                'evaluations' => $period->evaluations
            ];
        });

        return response()->json($result);
    }


    // Función para obtener las evaluaciones por periodo
    public function findEvaluationsByPeriod(string $periodId)
    {
        $evaluations = Evaluation::where('academic_management_period_id', $periodId)
            ->orderBy('id', 'asc') // Ordenar por id de manera ascendente
            ->get();

        if ($evaluations->isEmpty()) {
            return response()->json([
                "message" => "No se encontraron evaluaciones para el periodo con ID: $periodId"
            ], 404);
        }

        return response()->json($evaluations);
    }

}
