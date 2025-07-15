<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationStudent;
use App\Imports\StudentsImport;
use App\Models\Evaluation;
use App\Models\Student;
use App\Models\StudentTest;
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
            'evaluation_id' => 'required|integer|exists:evaluations,id',
        ], [
            'file.required' => 'Por favor seleccione un archivo',
            'file.mimes' => 'El archivo debe ser de tipo Excel (xlsx o xls)',
            'file.max' => 'El archivo no debe pesar más de 2MB',
            'evaluation_id.required' => 'Por favor seleccione una evaluación',
            'evaluation_id.exists' => 'La evaluación seleccionada no existe',
        ]);

        try {
            $evaluationId = $request->evaluation_id;
            $import = new StudentsImport($evaluationId);
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
        DB::beginTransaction();

        try {
            // Buscar estudiante por CI
            $student = Student::where('ci', $request->ci)->first();

            // Obtener evaluación
            $evaluation = Evaluation::with([
                'academicManagementPeriod.academicManagementCareer.career',
                'academicManagementPeriod.academicManagementCareer.academicManagement', // <-- Agregado
                'academicManagementPeriod.period'
            ])->findOrFail($request->evaluation_id);


            // Si existe, verificar si ya está asignado
            if ($student) {
                $alreadyAssigned = $student->evaluations()
                    ->where('evaluation_id', $evaluation->id)
                    ->exists();

                if ($alreadyAssigned) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'warning',
                        'message' => 'El estudiante ya está registrado y asociado a este examen.',
                        'data' => $student
                    ]);
                }
            } else {
                // Crear nuevo estudiante
                $student = Student::create([
                    'ci' => $request->ci,
                    'name' => $request->name,
                    'paternal_surname' => $request->paternal_surname,
                    'maternal_surname' => $request->maternal_surname,
                    'phone_number' => $request->phone_number,   
                    'birthdate' => Carbon::parse($request->birthdate),
                    'password' => Hash::make(($request->ci) . // Contraseña por defecto: CI + fecha de nacimiento
                    Carbon::parse($request->birthdate)->format('dmY')
                ), // Contraseña por defecto: CI + fecha de nacimiento (formato ddmmyyyy)
                ]);
            }
            // Asignar evaluación
            $student->evaluations()->attach($evaluation->id, [
                'status' => 'pendiente',
                'code' => 'TEMP',
                'start_time' => null,
                'end_time' => null,
                'correct_answers' => 0,
                'incorrect_answers' => 0,
                'not_answered' => 0,
                'score_obtained' => 0,
                'questions_order' => json_encode([]),
            ]);
            Evaluation::where('id', $evaluation->id)->increment('qualified_students');

            // Obtener student_test recién creado
            $studentTest = StudentTest::where('student_id', $student->id)
                ->where('evaluation_id', $evaluation->id)
                ->latest()
                ->first();

            // Generar código único
            $sigla = $evaluation->academicManagementPeriod->academicManagementCareer->career->initials;
            $periodName = $evaluation->academicManagementPeriod->period->level;
            $gestion = $evaluation->academicManagementPeriod->academicManagementCareer->academicManagement->year;
            $title = str_replace(' ', '', $evaluation->title);
            $periodName = str_replace(' ', '', $periodName);
            $code = strtoupper("{$title}-{$sigla}-{$periodName}/{$gestion}-{$studentTest->id}");

            // Actualizar el código
            $studentTest->update(['code' => $code]);


            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Estudiante registrado o asociado correctamente.',
                'data' => $student
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Ocurrió un error al registrar/asociar al estudiante.',
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
