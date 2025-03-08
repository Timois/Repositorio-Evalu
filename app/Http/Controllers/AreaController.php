<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationAreas;
use App\Models\Areas;
use App\Models\Period;
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
        if(!$area)
            return ["message:", "La area con el id:". $id . " no existe."];
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
    public function findById(Request $request, string $id)
    {
        $area = Areas::find($id);
        if(!$area)
            return ["message:", "La area con el id:" . $id . "no existe"];
        return response()->json($area);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function findAreasByCareer(Request $request, string $career_id)
    {
        $areas = Areas::where('career_id', $career_id)->get();
        return response()->json($areas);
    }
}
