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
            $excel = $request->file('file_name');
            $fileSize = $excel->getSize();

            // Validate file is not empty
            if ($fileSize === 0) {
                return response()->json([
                    'message' => 'Error en la importación',
                    'error' => 'El archivo Excel está vacío'
                ], 422);
            }

            // Pre-validate Excel format
            $tempImport = new QuestionBanKImport(null);
            $data = Excel::toArray($tempImport, $excel);

            // Validate format
            $validationMessages = $tempImport->validateFormat($data);
            if (!empty($validationMessages)) {
                return response()->json([
                    'message' => 'Error en el formato del Excel',
                    'errors' => $validationMessages
                ], 422);
            }

            // Check for duplicate file content
            $fileHash = hash_file('sha256', $excel->getRealPath());
            $existingImport = ExcelImports::where('file_hash', $fileHash)->exists();

            if ($existingImport) {
                return response()->json([
                    'message' => 'Error en la importación',
                    'error' => 'Este archivo ya ha sido importado previamente'
                ], 422);
            }

            // Continue with import logic
            $excelName = time() . '.' . $excel->getClientOriginalName();
            $name_path = public_path('private/exams/' . $excelName);

            $importExcel = new ExcelImports();
            $importExcel->file_name = $excelName;
            $importExcel->size = $fileSize;
            $importExcel->status = $request->status;
            $importExcel->file_path = $name_path;
            $importExcel->file_hash = $fileHash;
            $importExcel->save();

            // Move the file
            $path = $excel->move(public_path('private/exams'), $excelName);

            // Perform actual import
            $import = new QuestionBanKImport($importExcel->id);
            Excel::import($import, $path);

            // Get import messages
            $messages = $import->getMessages();

            return response()->json([
                'message' => 'Importación completada exitosamente',
                'success' => true,
                'details' => [
                    'file_name' => $excelName,
                    'file_size' => $fileSize,
                    'import_messages' => $messages
                ]
            ], 200);
        } catch (\Exception $e) {
            // Cleanup uploaded file if exists
            if (isset($path) && file_exists($path)) {
                unlink($path);
            }

            // Delete import record if created
            if (isset($importExcel)) {
                $importExcel->delete();
            }

            return response()->json([
                'message' => 'Error en la importación',
                'success' => false,
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
     * Show the form for editing the specified resource.
     */
    public function findAndUpdate(ValidationExcelImport $request, string $id)
    {
        $excel = ExcelImports::find($id);
        if (!$excel) {
            return ["message:", "El archivo con el id:" . $id . " no existe"];
        }
        $excel->status = $request->status;
        $excel->save();
        return $excel;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
