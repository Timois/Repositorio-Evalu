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
        $careers = Career::orderBy('id','ASC')->get();
        return response()->json($careers);
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
        $career->unit_id = $request->unit_id;
        $career->save();
        return $career;
    }

    /**
     * Display the specified resource.
     */
    public function findAndUpdate(ValidationsCareer $request, string $id)
    {
        

        $career = Career::find($id);
        if (!$career)
            return ["message:", "La carrera con id:" . $id . " no existe."];
        if($request->name)
            $career->name = strtolower($request->name);
        if($request)
            $career->initials = strtoupper($request->initials);

       $image = $request->file('logo');
        if(!$image){
            $career->save();
            return $career;
        }
            
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $imagePath = asset('images/units/' . $imageName);
        $image->move(public_path('images/units'), $imageName);
        $career->logo = $imagePath;

        $career->save();
        return $career;
    }

    public function findById(string $id) {
        $career = Career::find($id);
        if (!$career)
            return ["message:", "La carrera con id:" . $id . " no existe."];
        return response()->json($career);
    }

    public function assignManagement(ValidationAssignManagements $request){
        $academicManagementCareer = new AcademicManagementCareer();
        $academicManagementCareer->career_id=$request->career_id;
        $academicManagementCareer->academic_management_id=$request->academic_management_id;
        $academicManagementCareer->save();
        return ["message:", "Gestion asignado exitosamente"];
    }
    
    public function findAssignManagement(){
        $assign = AcademicManagementCareer::get();
        return response()->json($assign);
    }

    public function findByIdGestion(string $id){
        $management = AcademicManagementCareer::with('academic_management')->find($id);
        if(!$management)
            return ["message:", "La gestion con id:" . $id . " no existe."];
        return response()->json($management);
    }

}
