<?php

namespace App\Http\Controllers;

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
        $excelName = time() . '.' . $excel->getClientOriginalExtension();
        $path = $excel->storeAs('private/exams', $excelName);
    
        $importExcel = new ExcelImports();
        $importExcel->file_name = $excelName;
        $importExcel->original_name = $request->original_name;
        $importExcel->status = $request->status;
        $importExcel->file_path = $path;
        $importExcel->save();
    
        return response()->json(['message' => 'Archivo guardado exitosamente', 'data' => $importExcel]);
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
