<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationsAcademicManagement;
use App\Models\AcademicManagement;
use App\Models\AcademicManagementCareer;

class AcademicManagementController extends Controller
{

    public function find()
    {
        $academicManagements = AcademicManagement::orderby('id','ASC')->get();
        return response()->json($academicManagements);
    }
    public function create(ValidationsAcademicManagement $request)
    {
        $academicManagement = new AcademicManagement();
        $academicManagement->year = $request->year;
        $academicManagement->initial_date = $request->initial_date;
        $academicManagement->end_date = $request->end_date;
        $academicManagement->save();
        return $academicManagement;
    }

    public function findAndUpdate(string $id, ValidationsAcademicManagement $request)
    {
        $academicManagement = AcademicManagement::find($id);
        if (!$academicManagement)
            return ["message:", "La gestion academica con el id:" . $id . " no existe."];
        if ($request->year)
            $academicManagement->year = $request->year;
        if ($request->initial_date)
            $academicManagement->initial_date = $request->initial_date;
        if ($request->end_date)
            $academicManagement->end_date = $request->end_date;
        
        $academicManagement->save();
        return $academicManagement;
    }

    // Funcion para encontrar las gestiones asociadas a una carrera
    public function findByCareerId(string $careerId)
    {
        $academicManagements = AcademicManagementCareer::whereHas('academicManagement', function ($query) use ($careerId) {
            $query->where('career_id', $careerId);
        })->get();        
        return response()->json($academicManagements);
    }
}
