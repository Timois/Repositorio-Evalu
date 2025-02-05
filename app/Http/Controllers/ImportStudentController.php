<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationStudent;
use App\Imports\StudentsImport;
use App\Models\Student;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ImportStudentController extends Controller
{
    public function import(Request $request)
    {
        // Validar el archivo
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048',
        ], [
            'file.required' => 'Por favor seleccione un archivo',
            'file.mimes' => 'El archivo debe ser de tipo Excel (xlsx o xls)',
            'file.max' => 'El archivo no debe pesar más de 2MB'
        ]);

        try {
            // Importar el archivo
            Excel::import(new StudentsImport, $request->file('file'));

            return response()->json([
                'status' => 'success',
                'message' => 'Estudiantes importados correctamente'
            ], 200);
        } catch (\Exception $e) {
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
    public function findByName(Request $request) {
        $name = $request->input('name');
        $student = Student::where('name', 'like', "%$name%")->get();
        return response()->json($student);
    }
    
}
