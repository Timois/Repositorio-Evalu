<?php

namespace App\Http\Controllers;

use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\ValidationExcelImport;
use App\Imports\QuestionBanKImport;
use App\Models\ExcelImports;
use Illuminate\Http\Request;

class ExcelImportController extends Controller
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
    public function create(ValidationExcelImport $request)
    {
        try {
            // Procesar el archivo Excel
            $excel = $request->file('file_name');
            $fileSize = $excel->getSize();
            $excelName = time() . '.' . $excel->getClientOriginalName();
            $name_path = public_path('private/exams/' . $excelName);
    
            // Leer los datos del archivo Excel
            $datos = Excel::toArray(null, $excel);
    
            // Guardar información del archivo importado
            $importExcel = new ExcelImports();
            $importExcel->file_name = $excelName;
            $importExcel->size = $fileSize;
            $importExcel->status = $request->status;
            $importExcel->file_path = $name_path;
            $importExcel->save();
    
            // Mover el archivo a la carpeta adecuada
            $path = $excel->move(public_path('private/exams'), $excelName);
            $filePath = public_path('private/exams/' . $excelName);
    
            // Guardar el ID de la importación
            $excelImportId = $importExcel->id;
    
            // Importar datos usando el importador
            $import = new QuestionBanKImport($excelImportId);
            Excel::import($import, $path);
    
            // Obtener los mensajes del importador
            $messages = $import->getMessages();
    
            // Retornar los mensajes de la importación
            return response()->json([
                'message' => 'Importación completa',
                'details' => $messages,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error general en la importación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function find()
    {
        $importExcel = ExcelImports::orderBy('id', 'ASC')->get();
        return response()->json($importExcel);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
