<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationStudent;
use App\Imports\StudentsImport;
use App\Models\AcademicManagement;
use App\Models\AcademicManagementCareer;
use App\Models\AcademicManagementPeriod;
use App\Models\Student;
use Exception;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportStudentController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048',
            'academic_management_period_id' => 'required|integer|exists:academic_management_period,id',
        ], [
            'file.required' => 'Por favor seleccione un archivo',
            'file.mimes' => 'El archivo debe ser de tipo Excel (xlsx o xls)',
            'file.max' => 'El archivo no debe pesar más de 2MB',
            'academic_management_period_id.required' => 'El ID del período de gestión académica es obligatorio',
            'academic_management_period_id.exists' => 'El periodo académico seleccionado no existe',
        ]);

        try {
            $periodId = $request->academic_management_period_id;
            $import = new StudentsImport($periodId);
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();

            // Calcular estadísticas
            $totalRows = count($results);
            $successRows = count(array_filter($results, fn($r) => $r['estado'] === 'éxito'));
            $errorRows = $totalRows - $successRows;

            return response()->json([
                'status' => 'success',
                'resumen' => [
                    'total_filas' => $totalRows,
                    'exitosos' => $successRows,
                    'errores' => $errorRows
                ],
                'resultados_detallados' => $results
            ], 200);
        } catch (Exception $e) {
            $errorMessage = json_decode($e->getMessage(), true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return response()->json($errorMessage, 422);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Error al importar estudiantes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(ValidationStudent $request)
    {

        try {
            // Buscar estudiante
            $student = Student::where('ci', $request->ci)->first();

            if ($student) {
                // Asociar al periodo si no está ya
                $student->periods()->syncWithoutDetaching([$request->academic_management_period_id]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Estudiante ya registrado. Asociado al nuevo periodo correctamente.',
                    'data' => $student
                ]);
            }

            // Crear nuevo estudiante
            $birthdate = Carbon::createFromFormat('d-m-Y', $request->birthdate);
            $birthdateNumbers = $birthdate->format('dmY');
            $ciNumbers = preg_replace('/[^0-9]/', '', $request->ci);
            $password = Hash::make($ciNumbers . $birthdateNumbers);

            $student = Student::create([
                'ci' => $request->ci,
                'name' => $request->name,
                'paternal_surname' => $request->paternal_surname,
                'maternal_surname' => $request->maternal_surname,
                'phone_number' => $request->phone_number,
                'birthdate' => $birthdate,
                'password' => $password,
            ]);

            $student->periods()->attach($request->periodo_id);

            return response()->json([
                'status' => 'success',
                'message' => 'Estudiante registrado y asociado al periodo.',
                'data' => $student
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error al registrar al estudiante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function findAndUpdate(Request $request)
    {
        $student = Student::find($request->id);
        if (!$student)
            return ["message:", "El estudiante con id:" . $request->id . " no existe."];
        if ($request->ci)
            $student->ci = $request->ci;
        if ($request->name)
            $student->name = $request->name;
        if ($request->paternal_surname)
            $student->paternal_surname = $request->paternal_surname;
        if ($request->maternal_surname)
            $student->maternal_surname = $request->maternal_surname;
        if ($request->phone_number)
            $student->phone_number = $request->phone_number;
        if ($request->birthdate)
            $student->birthdate = $request->birthdate;
        $student->save();
        return response()->json($student);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function find()
    {
        $student = Student::orderBy('id', 'ASC')->get();
        return response()->json($student);
    }

    public function findStudentsByCareerId(string $careerId)
    {
        $students = DB::table('academic_management_period_student')
            ->join('students', 'academic_management_period_student.student_id', '=', 'students.id')
            ->join('academic_management_period', 'academic_management_period_student.academic_management_period_id', '=', 'academic_management_period.id')
            ->join('academic_management_career', 'academic_management_period.academic_management_career_id', '=', 'academic_management_career.id')
            ->join('periods', 'academic_management_period.period_id', '=', 'periods.id')
            ->where('academic_management_career.career_id', $careerId)
            ->select('students.*', 'periods.period as registered_period')
            ->distinct()
            ->get();

        return response()->json($students);
    }




    public function authenticate($ci, $password)
    {
        $student = Student::where('ci', $ci)->first();

        if (!$student) {
            return false;
        }

        return Hash::check($password, $student->password);
    }
    public function findById(Request $request)
    {

        $student = Student::where('id', $request->id)->first();
        if (!$student)
            return ["message:", "El estudiante con id:" . $request->id . " no existe."];
        return response()->json($student);
    }
    public function findByName(Request $request)
    {
        $name = $request->input('name');
        $student = Student::where('name', 'like', "%$name%")->get();
        return response()->json($student);
    }
}
