<?php

namespace App\Http\Controllers;

use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\ValidationExcelImport;
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
        // Aquí ya tienes datos validados
        $excel = $request->file('file_name');
        
        // Obtener el tamaño del archivo en bytes
        $fileSize = $excel->getSize(); // Tamaño en bytes
        
        // Convertir el tamaño a kilobytes (si es necesario)
        $fileSizeKB = $fileSize / 1024; // Tamaño en KB

        
        // Obtener el nombre del archivo con extensión
        $excelExtension = time() . '.' . $excel->getClientOriginalExtension();
        $excelName = time() . '.' . $excel->getClientOriginalName();

        // Guardar el archivo
        $path = $excel->storeAs('private/exams', $excelName);

        // Leer los datos del archivo Excel
        $datos = Excel::toArray(null, $excel);

        // Crear un objeto para almacenar la importación
        $importExcel = new ExcelImports();
        $importExcel->file_name = $excelName;
        $importExcel->size = $fileSizeKB; // Guardar el tamaño en KB
        $importExcel->status = $request->status;
        $importExcel->file_path = $path;

        // Guardar la importación en la base de datos (si es necesario)
        //$importExcel->save();

        // Retornar los datos del archivo Excel
        return json_encode($datos);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
