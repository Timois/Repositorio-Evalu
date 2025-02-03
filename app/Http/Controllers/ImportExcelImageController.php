<?php

namespace App\Http\Controllers;

use App\Imports\Kst;
use App\Imports\QuestionBankImport;
use App\Imports\QuestionImagesImport;
use App\Models\ExcelImports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ImportExcelImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function saveimgezip(Request $request)
    {
        // Validar que el archivo sea un ZIP
        $request->validate([
            'file_name' => 'required|mimes:zip|max:10240'
        ]);

        // Subir el archivo ZIP
        $zipFile = $request->file('file_name');
        $zipFileName = time();
        $zipPath = public_path("uploads") . DIRECTORY_SEPARATOR . $zipFileName;

        // Mover el archivo a la carpeta de destino 
        //dd($zipFileName);
        if ($zipFile->move(public_path("uploads" . DIRECTORY_SEPARATOR), $zipFileName)) {
            // Obtener el tamaño del archivo después de moverlo
            $fileSize = filesize($zipPath); // Usamos `filesize()` en lugar de `$zipFile->getSize()`

            // Verificar si el archivo tiene un tamaño mayor que 0
            if ($fileSize === 0) {
                return response()->json([
                    'message' => 'Error en la importación',
                    'error' => 'El archivo ZIP está vacío'
                ], 422);
            }

            // Ruta para extraer
            $excelImportId = time(); // Generar ID único
            //dd($excelImportId);
            $extractTo = public_path('uploads' . DIRECTORY_SEPARATOR . 'extracted_files' . DIRECTORY_SEPARATOR . $excelImportId . DIRECTORY_SEPARATOR);
            $pathaux = 'uploads' . DIRECTORY_SEPARATOR . 'extracted_files' . DIRECTORY_SEPARATOR .
                str_replace(['\\', '/'], DIRECTORY_SEPARATOR, pathinfo($zipFileName, PATHINFO_FILENAME)) . DIRECTORY_SEPARATOR;

            // Eliminar barras invertidas o diagonales innecesarias al principio o al final de la ruta
            $pathaux = rtrim($pathaux, DIRECTORY_SEPARATOR);
            $pathaux = ltrim($pathaux, DIRECTORY_SEPARATOR);
            $result = $this->extractZip($zipPath, $extractTo);
            //dd($result);
            // Guardar los detalles de la importación en la base de datos
            $importRecord = DB::table('excel_imports')->insert([
                'id' => $excelImportId,
                'file_name' => $zipFileName,
                'size' => $fileSize,  // Usamos el tamaño del archivo movido
                'status' => 'completado',
                'file_path' => $result['excel'],
            ]);

            if (!$importRecord) {
                throw new \Exception("Error al guardar el registro en excel_imports");
            }
            if ($result['success']) {

                try {
                    DB::beginTransaction(); // Iniciar la transacción

                    // Crear los parámetros para la importación
                    $importParams = [
                        'excel_import_id' => $excelImportId,
                        'extractedPath' => $pathaux
                    ];

                    // Crear la instancia de importación con los parámetros
                    $import = new QuestionImagesImport($importParams);
                    Excel::import($import, $result['excel']);

                    //dd('entro' ,$import);
                    // Verificar si hubo errores durante la importación
                    $messages = $import->getMessages();
                    if (!empty($messages)) {
                        throw new \Exception(implode(", ", $messages));
                    }

                    DB::commit(); // Confirmar la transacción si todo salió bien

                    return response()->json([
                        'message' => 'Importación completada exitosamente',
                        'status' => 'completado',
                    ], 200);
                } catch (\Exception $e) {
                    //dd('me entre al roll bak');
                    DB::rollback(); // Deshacer cambios en caso de error

                    return response()->json([
                        'message' => 'Error durante la importación',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }

            return response()->json([
                'message' => 'Error al extraer el ZIP',
                'error' => $result['message']
            ], 500);
        } else {
            return response()->json([
                'message' => 'Error al mover el archivo',
                'error' => 'No se pudo mover el archivo ZIP al directorio de carga'
            ], 500);
        }
    }


    private function extractZip($zipPath, $extractTo)
    {
        //dd($zipPath, $extractTo);
        $zip = new \ZipArchive();

        if ($zip->open($zipPath) === true) {
            
            // Crear la carpeta si no existe
            if (!File::exists($extractTo)) {
                File::makeDirectory($extractTo, 0755, true);
            }

            // Extraer el contenido
            $zip->extractTo($extractTo);
            $zip->close();

            // Obtener la lista de archivos extraídos
            $extractedFiles = File::allFiles($extractTo);
            
            // Filtrar solo los archivos Excel (.xlsx, .xls)
            $excelFiles = array_filter($extractedFiles, function ($file) {
                return in_array(strtolower($file->getExtension()), ['xlsx', 'xls']);
            });
            
            // Si se encuentran archivos Excel, retornar el primero
            if (!empty($excelFiles)) {
                $firstExcelFile = reset($excelFiles);  // Obtener el primer archivo Excel
                $excelFilePath = $firstExcelFile->getRelativePathname();  // Ruta relativa
                $fullPath = $extractTo . $excelFilePath;  // Ruta completa al archivo

                return [
                    'success' => true,
                    'excel' => $fullPath, // Ruta completa del primer archivo Excel encontrado
                    'path' => $extractTo
                ];
            }

            // Si no se encuentra ningún archivo Excel
            return [
                'success' => false,
                'message' => 'No se encontraron archivos Excel en el ZIP.'
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al abrir el archivo ZIP.'
        ];
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function find()
    {
        $excelImage = ExcelImports::orderBy('id', 'ASC')->get();
        return response()->json($excelImage);
    }


    public function getSavePath(string $relativeName, Request $request)
    {
        //buscar en $data el archivo que contenga el nombre original igual a $relativeName
        foreach ($request->file('file_name') as $key => $value) {
            if ($key > 0) {
                if ($value->getClientOriginalName() === $relativeName) {
                    $imageName = Str::uuid() . '.' . $value->getClientOriginalExtension();
                    $value->move(public_path('images/questions/'), $imageName);
                    return $imageName;
                }
            }
        }
        return null;
    }
}
