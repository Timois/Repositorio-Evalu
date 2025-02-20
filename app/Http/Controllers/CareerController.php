<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationAssignManagements;
use Illuminate\Http\Request;

use App\Http\Requests\ValidationsCareer;
use App\Models\AcademicManagementCareer;
use App\Models\AcademicManagementPeriod;
use App\Models\Career;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Validations;

class CareerController extends Controller
{

    public function find()
    {
        $careers = Career::orderBy('id', 'ASC')->get();
        return response()->json($careers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function  create(ValidationsCareer $request)
    {
        // Guardar la imagen en el servidor
        $image = $request->file('logo');
        $idU = $request->unit_id;
        $name = DB::table('units')->where('id', '=', $idU)->value('name');
        $basePath = public_path('images' . DIRECTORY_SEPARATOR . 'units');
        $imageDirectory = $basePath . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . $request->name . DIRECTORY_SEPARATOR . 'Logo';
        // Asegurar que el directorio existe antes de mover la imagen
        if (!file_exists($imageDirectory)) {
            mkdir($imageDirectory, 0777, true);
        }
        // Generar el nombre de la imagen con marca de tiempo
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        
        // Mover la imagen a la ruta generada
        $image->move($imageDirectory, $imageName);
        $imageUrl = asset('images/units/' . $name . '/' . $request->name . '/Logo/' . $imageName);
            
        $career = new Career();
        $career->name = strtolower($request->name);
        $career->initials = strtoupper($request->initials);
        $career->logo = $imageUrl;
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
        if ($request->name)
            $career->name = strtolower($request->name);
        if ($request)
            $career->initials = strtoupper($request->initials);

        $image = $request->file('logo');
        if (!$image) {
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

    public function findById(string $id)
    {
        $career = Career::find($id);
        if (!$career)
            return ["message:", "La carrera con id:" . $id . " no existe."];
        return response()->json($career);
    }

    public function assignManagement(ValidationAssignManagements $request)
    {
        $academicManagementCareer = new AcademicManagementCareer();
        $academicManagementCareer->career_id = $request->career_id;
        $academicManagementCareer->academic_management_id = $request->academic_management_id;
        $academicManagementCareer->save();
        return ["message:", "Gestion asignado exitosamente"];
    }

    public function findAssignManagement()
    {
        $assign = AcademicManagementCareer::get();
        return response()->json($assign);
    }

    public function findByIdAssign(string $careerId)
    {
        $managements = AcademicManagementCareer::where('career_id', $careerId)
            ->with(['academicManagement' => function ($query) {
                $query->select('id', 'initial_date', 'end_date');
            }])
            ->get();

        if ($managements->isEmpty())
            return response()->json([]);

        $result = $managements->map(function ($management) {
            return [
                'id' => $management->academicManagement->id,
                'name' => $management->career->name,
                'initial_date' => $management->academicManagement->initial_date,
                'end_date' => $management->academicManagement->end_date,
                'academic_management_career_id' => $management->id
            ];
        });

        return response()->json($result);
    }

    public function findAndUpdateAssign(ValidationAssignManagements $request, string $id)
    {
        $update = AcademicManagementCareer::find($id);
        if (!$update)
            return ["message:", "La gestion academica no existe con el id:" . $id . " no existe."];
        $update->academic_management_id = $request->academic_management_id;
        $update->save();
        return $update;
    }


    public function findPeriodByIdAssign(string $academicManagementCareerId)
    {
        $periods = AcademicManagementPeriod::where('academic_management_career_id', $academicManagementCareerId)
            ->with(['period' => function ($query) {
                $query->select('id', 'period');
            }])
            ->get();

        if ($periods->isEmpty())
            return response()->json([]);

        $result = $periods->map(function ($periods) {
            return [
                'id' => $periods->id,
                'period_id' => $periods->period->id,
                'period' => $periods->period->period,
                'initial_date' => $periods->initial_date,
                'end_date' => $periods->end_date
            ];
        });

        return response()->json($result);
    }


    public function createAssign(ValidationAssignManagements $request)
    {
        $academicManagementCareer = new AcademicManagementCareer();
        $academicManagementCareer->career_id = $request->career_id;
        $academicManagementCareer->academic_management_id = $request->academic_management_id;
        $academicManagementCareer->save();

        return ["message:", "Gestion asignado exitosamente"];
    }
}
