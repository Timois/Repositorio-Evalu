<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationAreas;
use App\Models\Areas;
use App\Models\Period;
use App\Models\QuestionBank;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function find()
    {
        $area = Areas::orderBy('id', 'ASC')->get();
        return response()->json($area);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ValidationAreas $request)
    {
        $area = new Areas();
        $area->name = $request->name;
        $area->description = $request->description;
        $area->status = 'activo';
        $area->career_id = $request->career_id;
        $area->save();
        return $area;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function findAndUpdate(ValidationAreas $request, string $id)
    {
        $area = Areas::find($id);
        if (!$area)
            return ["message:", "La area con el id:" . $id . " no existe."];
        if ($request->name)
            $area->name = $request->name;
        if ($request->description)
            $area->description = $request->description;

        $area->save();
        return $area;
    }

    /**
     * Display the specified resource.
     */
    public function findById(string $id)
    {
        $area = Areas::find($id);
        if (!$area)
            return ["message:", "La area con el id:" . $id . "no existe"];
        return response()->json($area);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $area = Areas::find($id);
        if (!$area)
            return ["message:", "La area con el id:" . $id . " no existe."];
        if ($area->status == 'activo') {
            $area->status = 'inactivo';
        } else {
            $area->status = 'activo';
        }
        $area->save();
        $response = [
            'message' => 'Estado actualizado correctamente',
            'area' => $area->name
        ];
        return response()->json($response);
    }

    public function findAreasByCareer(string $career_id)
    {
        $areas = Areas::where('career_id', $career_id)->get();
        return response()->json($areas);
    }

    public function questionsByArea(string $e)
    {
        $questions = QuestionBank::where('excel_import_id', $e)->get();

        if ($questions->isEmpty()) {
            return response()->json(['message' => 'No hay preguntas para esta área'], 404);
        }

        return response()->json($questions);
    }

    public function cantityQuestionsByArea(string $area_id)
    {
        $questions = QuestionBank::where('area_id', $area_id)->count();
        return response()->json($questions);
    }
}
