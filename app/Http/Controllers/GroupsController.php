<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationGroup;
use App\Models\Group;
use App\Models\StudentTest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GroupsController extends Controller
{
    public function find()
    {
        $groups = Group::orderBy('id', 'asc')->get();
        return response()->json($groups);
    }

    public function findById(string $id)
    {
        $group = Group::find($id);
        if ($group) {
            return response()->json($group);
        } else {
            return response()->json(['message' => 'No se encontro el grupo'], 404);
        }
    }

    // Lista los grupos de una evaluación específica
    public function findGroupsByEvaluation(string $evaluationId)
    {
        $groups = Group::where('evaluation_id', $evaluationId)->get();
        return response()->json($groups);
    }

    public function create(ValidationGroup $request)
    {
        $totalStudents = StudentTest::where('evaluation_id', $request->evaluation_id)->count();
        $group = new Group();
        $group->evaluation_id = $request->evaluation_id;
        $group->name = $request->name;
        $group->description = $request->description;
        $group->total_students = $totalStudents;
        $group->start_time  = $request->start_time;
        $group->end_time = $request->end_time;
        $group->save();
        return response()->json($group, 201);
    }
    public function update(ValidationGroup $request, string $id)
    {
        $group = Group::find($id);
        if (!$group) {
            return response()->json(['message' => 'No se encontro el grupo'], 404);
        }
        if ($request->has('evaluation_id')) {
            $group->evaluation_id = $request->evaluation_id;
        }

        if ($request->has('name')) {
            $group->name = $request->name;
        }

        if ($request->has('description')) {
            $group->description = $request->description;
        }

        if ($request->has('start_time')) {
            $group->start_time = $request->start_time;
        }

        if ($request->has('end_time')) {
            $group->end_time = $request->end_time;
        }

        // Recalcular total_students si cambió evaluation_id
        if ($request->has('evaluation_id')) {
            $totalStudents = StudentTest::where('evaluation_id', $request->evaluation_id)->count();
            $group->total_students = $totalStudents;
        }

        $group->save();
        return response()->json($group, 200);
    }
}
