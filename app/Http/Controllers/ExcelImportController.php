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

            // Validar que el archivo no esté vacío
            if ($fileSize === 0) {
                return response()->json([
                    'message' => 'Error en la importación',
                    'error' => 'El archivo Excel está vacío'
                ], 422);
            }

            // Primero verificar el formato del Excel sin guardar nada
            $tempImport = new QuestionBanKImport(null); // Pasamos null porque aún no tenemos ID
            $data = Excel::toArray($tempImport, $excel);

            // Validar el formato usando el método de validación de QuestionBankImport
            $validationMessages = $tempImport->validateFormat($data);

            if (!empty($validationMessages)) {
                return response()->json([
                    'message' => 'Error en el formato del Excel',
                    'errors' => $validationMessages
                ], 422);
            }

            // Si el formato es válido, procedemos con el guardado
            $excelName = time() . '.' . $excel->getClientOriginalName();
            $name_path = public_path('private/exams/' . $excelName);

            // Guardar información del archivo importado
            $importExcel = new ExcelImports();
            $importExcel->file_name = $excelName;
            $importExcel->size = $fileSize;
            $importExcel->status = $request->status;
            $importExcel->file_path = $name_path;
            $importExcel->save();

            // Mover el archivo
            $path = $excel->move(public_path('private/exams'), $excelName);

            // Ahora sí realizamos la importación real con el ID
            $import = new QuestionBanKImport($importExcel->id);
            Excel::import($import, $path);

            // Obtener mensajes de la importación
            $messages = $import->getMessages();

            return response()->json([
                'message' => 'Importación completa',
                'details' => $messages,
            ], 200);
        } catch (\Exception $e) {
            // Limpiar archivos si se crearon
            if (isset($path) && file_exists($path)) {
                unlink($path);
            }

            // Eliminar registro si se creó
            if (isset($importExcel)) {
                $importExcel->delete();
            }

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
     * Show the form for editing the specified resource.
     */
    public function edit(ValidationExcelImport $request, string $id)
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
