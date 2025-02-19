<?php

namespace App\Http\Controllers;

use App\Imports\QuestionImagesImport;
use App\Models\ExcelImports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ImportExcelImageController extends Controller
{
    /**
     * Guarda un archivo ZIP y procesa su contenido.
     */
    public function saveimgezip(Request $request)
    {
        try {
            // Validar que el archivo sea un ZIP
            $request->validate([
                'file_name' => 'required|mimetypes:application/zip,application/x-zip-compressed|max:10240',
                'career' => 'required|max:255|regex:/[a-zA-Zñ]+/',
                'sigla' => 'required|string|max:10|regex:/[a-zA-Zñ]+/',
                'status' => 'required|in:completado,error',
            ]);

            // Subir el archivo ZIP
            $zipFile = $request->file('file_name');
            $zipFileName = time();
            $zipPath = public_path("uploads") . DIRECTORY_SEPARATOR . $zipFileName;

            // Mover el archivo ZIP
            if (!$zipFile->move(public_path("uploads" . DIRECTORY_SEPARATOR), $zipFileName)) {
                throw new \Exception('No se pudo mover el archivo ZIP al directorio de carga');
            }

            // Obtener el tamaño del archivo
            $fileSize = filesize($zipPath);
            if ($fileSize === 0) {
                throw new \Exception('El archivo ZIP está vacío');
            }

            // Extraer el ZIP
            $excelImportId = time();
            $extractTo = public_path('uploads' . DIRECTORY_SEPARATOR . 'extracted_files' . DIRECTORY_SEPARATOR . $excelImportId . DIRECTORY_SEPARATOR);
            $result = $this->extractZip($zipPath, $extractTo);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }
            DB::beginTransaction();

            try {
                $importParams = [
                    'sigla' => $request->sigla,
                    'extractedPath' => $extractTo,
                    'validateOnly' => true
                ];

                $import = new QuestionImagesImport($importParams);
                Excel::import($import, $result['excel']);

                $analysis = $import->getImportSummary();

                // Verificar porcentaje de duplicados
                if ($analysis['total_duplicates'] > 0) {
                    $totalQuestions = $analysis['total_duplicates'] + $analysis['total_valid'];
                    $duplicatePercentage = ($analysis['total_duplicates'] / $totalQuestions) * 100;

                    if ($duplicatePercentage > 50) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Demasiadas preguntas duplicadas',
                            'success' => false,
                            'analysis' => [
                                'total_preguntas' => $totalQuestions,
                                'duplicadas' => $analysis['total_duplicates'],
                                'porcentaje_duplicados' => round($duplicatePercentage, 2) . '%',
                                'detalle_duplicados' => $analysis['duplicate_details']
                            ]
                        ], 422);
                    }
                }

                // Proceder con la importación
                $importRecordId = DB::table('excel_imports')->insertGetId([
                    'file_name' => $zipFileName,
                    'career' => strtolower($request->career),
                    'sigla' => strtoupper($request->sigla),
                    'size' => $fileSize,
                    'status' => $request->status,
                    'file_path' => $result['excel'],
                ]);

                // Importación real
                $importParams['excel_import_id'] = $importRecordId;
                $importParams['validateOnly'] = false;
                $import = new QuestionImagesImport($importParams);
                Excel::import($import, $result['excel']);

                DB::commit();

                // Obtener resumen detallado
                $importSummary = $import->getImportSummary();

                return response()->json([
                    'message' => 'Importación completada',
                    'success' => true,
                    'resumen' => [
                        'total_procesadas' => $importSummary['registradas']['total'] +
                            $importSummary['no_registradas']['total'] +
                            $importSummary['duplicadas']['total'],
                        'preguntas_registradas' => [
                            'total' => $importSummary['registradas']['total'],
                            'detalle' => $importSummary['registradas']['detalle']
                        ],
                        'preguntas_no_registradas' => [
                            'total' => $importSummary['no_registradas']['total'],
                            'detalle' => $importSummary['no_registradas']['detalle']
                        ],
                        'preguntas_duplicadas' => [
                            'total' => $importSummary['duplicadas']['total'],
                            'detalle' => $importSummary['duplicadas']['detalle']
                        ]
                    ]
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            // ... limpieza de archivos ...
            return response()->json([
                'message' => 'Error en la importación',
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Extrae el contenido de un archivo ZIP.
     */
    private function extractZip($zipPath, $extractTo)
    {
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
     * Obtiene la lista de importaciones realizadas.
     */
    public function find()
    {
        $excelImage = ExcelImports::orderBy('id', 'ASC')->get();
        return response()->json($excelImage);
    }

    /**
     * Guarda una imagen y devuelve su nombre.
     */
    public function getSavePath(string $relativeName, Request $request)
    {
        // Buscar en $data el archivo que contenga el nombre original igual a $relativeName
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
