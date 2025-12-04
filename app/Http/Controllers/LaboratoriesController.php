<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationLaboratorie;
use App\Models\Laboratorie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaboratoriesController extends Controller
{
    public function find()
    {
        $labs = Laboratorie::orderBy('id', 'ASC')->with('career')->get();
        return response()->json($labs);
    }

    public function findByCareerId(string $careerId)
    {
        $labs = Laboratorie::where('career_id', $careerId)->orderBy('id', 'ASC')->get();
        return response()->json($labs);
    }
    public function findById(string $id)
    {
        $lab = Laboratorie::find($id);
        if ($lab) {
            return response()->json($lab);
        } else {
            return response()->json(['message' => 'No se encontró el laboratorio'], 404);
        }
    }

    public function create(ValidationLaboratorie $request)
    {
        $lab = new Laboratorie();
        $lab->career_id = $request->career_id;
        $lab->name = $request->name;
        $lab->location = $request->location;
        $lab->equipment_count = $request->equipment_count;
        $lab->save();
        return response()->json($lab);
    }

    public function update(ValidationLaboratorie $request, string $id)
    {

        $lab = Laboratorie::find($id);
        if (!$lab) {
            return response()->json(['message' => 'No se encontró el laboratorio'], 404);
        }
        if ($request->name) {
            $lab->name = $request->name;
        }
        if ($request->location) {
            $lab->location = $request->location;
        }
        if ($request->equipment_count) {
            $lab->equipment_count = $request->equipment_count;
        }
        $lab->save();
        return response()->json($lab);
    }

    // Obtener el horario de un laborotorio por su ID
    public function getScheduleById(Request $request)
    {
        $ids = $request->ids;
        // return response()->json($ids);
        $miValor = implode(',', array_fill(0, count($ids), '?'));
        $labs = DB::select("
        select 	a.name as nombre_laboratorio, 
		        a.equipment_count as capacidad, 
		        b.name nombre_grupo, 
		        b.laboratory_id as id_laboratorio,
		        b.start_time as start,
		        b.end_time as end,
		        c.title as nombre_evaluacion, 
		        b.evaluation_id as id_evaluacion
        from 	laboratories a, groups b, evaluations c
        where 	a.id = b.laboratory_id and
        		c.id = b.evaluation_id and
                b.laboratory_id in ($miValor)
        ", $ids);

        return response()->json($labs);
    }
}
