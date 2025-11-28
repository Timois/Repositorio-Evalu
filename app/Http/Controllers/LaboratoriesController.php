<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationLaboratorie;
use App\Models\Laboratorie;
use Illuminate\Http\Request;

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
    public function getScheduleById(string $id)
    {
        $lab = Laboratorie::with('groups.evaluation')->find($id);

        if (!$lab) {
            return response()->json(['message' => 'No se encontró el laboratorio'], 404);
        }

        $schedule = $lab->groups->map(function ($group) {
            return [
                'id'    => $group->id,
                'title' => $group->name,
                'start' => $group->evaluation->date_of_realization . ' ' . $group->start_time,
                'end'   => $group->evaluation->date_of_realization . ' ' . $group->end_time,
            ];
        });

        return response()->json($schedule);
    }
}
