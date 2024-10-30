<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationAssignManagements;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validations\ValidationException;
use Illuminate\Support\Facades\Schema;

use App\Http\Requests\ValidationsCareer;
use App\Models\AcademicManagement;
use App\Models\AcademicManagementCareer;
use App\Models\Career;
use Illuminate\Validation\ValidationException as ValidationValidationException;

class CareerController extends Controller
{

    public function find()
    {
        $careers = Career::get();
        return $careers;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function  create(ValidationsCareer $request)
    {
        // Guardar la imagen en el servidor
        $image = $request->file('logo');
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $imagePath = asset('images/careers/' . $imageName);
        $image->move(public_path('images/careers'), $imageName);

        $career = new Career();
        $career->name = strtolower($request->name);
        $career->initials = strtoupper($request->initials);
        $career->logo = $imagePath;
        $career->user_id = $request->user_id;
        $career->unit_id = $request->unit_id;
        $career->save();
        return $career;
    }

    /**
     * Display the specified resource.
     */
    public function findAndUpdate(ValidationsCareer $request, string $id)
    {
        $image = $request->file('logo');
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $imagePath = asset('images/careers/' . $imageName);
        $image->move(public_path('images/careers'), $imageName);

        $career = Career::find($id);
        if (!$career)
            return ["message:", "La carrera con id:" . $id . " no existe."];
        $career->name = strtolower($request->name);
        $career->logo = $imagePath;
        $career->initials = strtoupper($request->initials);
        $career->save();
        return $career;
    }

    public function findById(string $id) {
        $career = Career::find($id);
        if (!$career)
            return ["message:", "La carrera con id:" . $id . " no existe."];
        return $career;
    }

    public function assignManagement(ValidationAssignManagements $request){
        $academicManagementCareer = new AcademicManagementCareer();
        $academicManagementCareer->career_id=$request->career_id;
        $academicManagementCareer->academic_management_id=$request->academic_management_id;
        $academicManagementCareer->save();
        return ["message:", "Gestion asignado exitosamente"];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function unsubscribe(string $id)
    {
        $deleted = DB::table('careers')->where("id", $id)->update(["status" => "inactivo"]);
        return $deleted;
    }
}
