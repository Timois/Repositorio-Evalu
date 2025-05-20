<?php

namespace App\Http\Controllers;

use App\Models\Group;
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

    public function create(Request $request)
    {
        $request->validate([
            'evaluation_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'total_students' => 'required|integer|min:1',
        ]);

        $group = new Group();
        $group->evaluation_id = $request->input('evaluation_id');
        $group->name = $request->input('name');
        $group->description = $request->input('description');
        $group->total_students = $request->input('total_students');
        $group->save();

        return response()->json($group, 201);
    }
}
