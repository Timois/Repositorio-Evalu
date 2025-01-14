<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationAreas;
use App\Models\Areas;
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
        $area->save();
        return $area;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function findAndUpdate(ValidationAreas $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
