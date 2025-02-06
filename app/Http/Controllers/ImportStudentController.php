<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationStudent;
use App\Imports\StudentsImport;
use App\Models\Student;
use Exception;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ImportStudentController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048',
        ], [
            'file.required' => 'Por favor seleccione un archivo',
            'file.mimes' => 'El archivo debe ser de tipo Excel (xlsx o xls)',
            'file.max' => 'El archivo no debe pesar más de 2MB'
        ]);

        try {
            $import = new StudentsImport();
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
    /**
     * Store a newly created resource in storage.
     */
    public function find()
    {
        $student = Student::orderBy('id', 'ASC')->get();
        return response()->json($student);
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
