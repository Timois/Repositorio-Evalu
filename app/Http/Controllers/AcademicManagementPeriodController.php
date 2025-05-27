<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationAcademicManagementPeriod;
use App\Models\AcademicManagementCareer;
use App\Models\AcademicManagementPeriod;
use App\Models\Career;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

use function Pest\Laravel\json;

class AcademicManagementPeriodController extends Controller
{
    public function find()
    {
        $academicManagementPeriod = AcademicManagementPeriod::orderBy('id', 'ASC')->get();
        return response()->json($academicManagementPeriod);
    }

    public function findByIdCareer(string $id)
    {
        $academic_career = AcademicManagementPeriod::with('period_id')->find($id);
        dd($academic_career);
        if (!$academic_career)
            return ["message:", "El periodo con id:" . $id . " no existe."];
        return response()->json($academic_career);
    }
    public function create(ValidationAcademicManagementPeriod $request)
    {
        $academicManagementPeriod = new AcademicManagementPeriod();
        $academicManagementPeriod->initial_date = $request->initial_date;
        $academicManagementPeriod->end_date = $request->end_date;
        $academicManagementPeriod->academic_management_career_id = $request->academic_management_career_id;
        $academicManagementPeriod->period_id = $request->period_id;
        if (Carbon::parse($request->end_date)->lt(Carbon::now())) {
            $academicManagementPeriod->status = "finalizado";
        } else {
            $academicManagementPeriod->status = "aperturado";
        }

        $academicManagementPeriod->save();
        return response()->json()->$academicManagementPeriod;
    }

    public function findAndUpdate(ValidationAcademicManagementPeriod $request, string $id)
    {
        $update = AcademicManagementPeriod::find($id);

        if (!$update)
            return ["message:" => "El periodo de la gestión académica con el id: " . $id . " no existe."];

        if ($request->initial_date)
            $update->initial_date = $request->initial_date;

        if ($request->end_date)
            $update->end_date = $request->end_date;

        $update->academic_management_career_id = $request->academic_management_career_id;
        $update->period_id = $request->period_id;

        // Verificar si la fecha fin ya pasó
        if (Carbon::parse($update->end_date)->lt(Carbon::now())) {
            $update->status = "finalizado";
        } else {    
            $update->status = "aperturado";
        }

        $update->save();

        return response()->json()->$update;
    }


    // Filtrar los periodos asignados a una carrera en una gestion academica
    public function findPeriodsByCareerManagement($career_id, $academic_management_id)
    {
        $relation = AcademicManagementCareer::where('career_id', $career_id)
            ->where('academic_management_id', $academic_management_id)
            ->first();

        if (!$relation) {
            return response()->json([
                "message" => "No se encontró relación entre la carrera y la gestión académica."
            ], 404);
        }

        // Buscar los periodos que pertenecen a esa relación
        $periods = AcademicManagementPeriod::where('academic_management_career_id', $relation->id)
            ->with(['period'])
            ->orderBy('initial_date', 'ASC')
            ->get();

        return response()->json($periods);
    }
}
