<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationManagementExtension;
use App\Models\ManagementExtension;
use Illuminate\Http\Request;

class ManagementExtensionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ValidationManagementExtension $request)
    {
        $managementExtension = new ManagementExtension();
        $managementExtension->date_extension=$request->date_extension;
        $managementExtension->academic_management_id = $request->academic_management_id;
        $managementExtension->save();
        return $managementExtension;
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function findAndUpdate(ValidationManagementExtension $request, string $id)
    {
        $managementExtension = ManagementExtension::find($id);
        if(!$managementExtension)
            return ["message:", "La extension de gestion academica con el id:" . $id . " no existe."];
        $managementExtension->date_extension = $request->date_extension;
        $managementExtension->academic_management_id = $request->academic_management_id;
        $managementExtension->save();
        return $managementExtension;
    }

    /**
     * Update the specified resource in storage.
     */
    public function find()
    {
        $managementExtension = ManagementExtension::orderBy('id','ASC')->get();
        return response()->json($managementExtension);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
