<?php

namespace App\Http\Controllers;

use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\ValidationExcelImport;
use App\Imports\QuestionBanKImport;
use App\Models\ExcelImports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            $area_id = $request->area_id;
            $periodId = $request->academic_management_period_id;
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
            $tempImport =    new QuestionBankImport(null, null, null);
            $data = Excel::toArray($tempImport, $excel);

            // Validate format
            $validationMessages = $tempImport->validateFormat($data);
            if (!empty($validationMessages)) {
                return response()->json([
                    'message' => 'Error en el formato del Excel',
                    'errors' => $validationMessages
                ], 422);
            }

            // Continue with import logic
            $excelName = time() . '.' . $excel->getClientOriginalName();
            $name_path = public_path('private' . DIRECTORY_SEPARATOR . 'exams' . DIRECTORY_SEPARATOR . $excelName);

            $importExcel = new ExcelImports();
            $importExcel->file_name = $excelName;
            $importExcel->size = $fileSize;
            $importExcel->status = $request->status;
            $importExcel->description = $request->description;
            $importExcel->file_path = $name_path;
            $importExcel->save();

            // Move the file
            $path = $excel->move(public_path('private' . DIRECTORY_SEPARATOR . 'exams'), $excelName);

            // Perform actual import
            $import = new QuestionBanKImport($importExcel->id, $area_id, $periodId);
            Excel::import($import, $path);

            // Get import messages
            $messages = $import->getMessages();

            return response()->json([
                'message' => 'Importación completada exitosamente',
                'success' => $messages,
            ], 200);
        } catch (\Exception $e) {
            // Eliminar el archivo si fue subido
            if (isset($path) && file_exists($path)) {
                unlink($path);
            }

            // Eliminar el registro del import si fue creado
            if (isset($importExcel) && $importExcel instanceof ExcelImports) {
                $importExcel->delete();
            }

            return response()->json([
                'message' => 'Error en la importación',
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_trace' => config('app.debug') ? $e->getTraceAsString() : null, // Solo en desarrollo
            ], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function find($areaId)
    {
        $importExcel = DB::table('excel_imports')
            ->join('bank_questions', 'excel_imports.id', '=', 'bank_questions.excel_import_id')
            ->where('bank_questions.area_id', $areaId)
            ->select('excel_imports.*')
            ->distinct()
            ->orderBy('excel_imports.id', 'ASC')
            ->get();
        if ($importExcel->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron archivos excel para el área especificada.',
            ], 404);
        }
        return response()->json($importExcel);
    }

    public function findAreaByExcel($excelId)
    {
        $area = DB::table('bank_questions')
            ->join('areas', 'bank_questions.area_id', '=', 'areas.id')
            ->where('bank_questions.excel_import_id', $excelId)
            ->select('areas.*')
            ->distinct()
            ->get();

        if ($area->isEmpty()) {
            return response()->json([
                'message' => 'No se encontró un área asociada al archivo Excel especificado.',
            ], 404);
        }

        return response()->json($area);
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
        $excel->description = $request->description;
        $excel->save();
        return $excel;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $excel = ExcelImports::find($id);
        if (!$excel) {
            return ["message:", "El archivo con el id:" . $id . " no existe"];
        }
        $excel->delete();
        return $excel;
    }
}
