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

    public function create(ValidationGroup $request)
    {
        $totalStudents = StudentTest::where('evaluation_id', $request->evaluation_id)->count();
        dd($totalStudents);
        $group = new Group();
        $group->evaluation_id = $request->evaluation_id;
        $group->name = $request->name;
        $group->description = $request->description;
        $group->total_students = $totalStudents;
        $group->start_time  =$request->start_time;
        $group->end_time = $request->end_time;
        //$group->save();
        return response()->json($group, 201);
    }
}
