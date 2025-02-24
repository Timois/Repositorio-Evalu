<?php

namespace App\Imports;

use App\Models\AnswerBank;
use App\Models\QuestionBank;
use App\Models\Areas;
use App\Models\Career; // Para obtener la carrera relacionada
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class QuestionImagesImport implements ToCollection
{
    protected $excelImportId;
    protected $messages = [];
    protected $extractedPath;
    protected $sigla;
    protected $validateOnly;
    protected $registeredQuestions = [];
    protected $skippedQuestions = [];
    protected $processedRows = [];
    protected $duplicateDetails = [];
    protected $areaId;

    protected $requiredColumns = [
        'pregunta',
        'descripcion',
        'dificultad',
        'tipo',
        'imagen',
        'opcion1',
        'opcion2',
        'opcion3',
        'opcion4',
        'respuesta correcta'
    ];

    public function __construct(array $params)
    {
        $this->excelImportId = $params['excel_import_id'];
        $this->extractedPath = $params['extractedPath'];
        $this->areaId = $params['areaId'];
        $this->validateOnly = $params['validateOnly'] ?? false;
    }

    /**
     * Carga la sigla de la carrera asociada al área
     */

    public function collection(Collection $rows)
    {

        // Verificar si hay filas en el Excel
        if ($rows->isEmpty()) {
            $this->messages[] = "El archivo Excel está vacío.";
            return;
        }

        // Obtener las cabeceras del Excel
        $headers = $rows[0]->toArray();

        // Validar que los headers coincidan con los required columns
        $headersDiff = array_diff($this->requiredColumns, $headers);
        if (!empty($headersDiff)) {
            $this->messages[] = "Columnas faltantes: " . implode(', ', $headersDiff);
            return; // Detener el procesamiento si faltan columnas
        }

        // Procesar cada fila
        foreach ($rows as $index => $row) {
            // Saltar la primera fila (headers)
            if ($index === 0) {
                continue;
            }
            $rowArray = $row->toArray();

            // Verificar si la fila está completamente vacía
            if (empty(array_filter($rowArray, function ($value) {
                return $value !== null && $value !== '';
            }))) {
                Log::info('Fila ' . ($index + 1) . ' está vacía, terminando el procesamiento.');
                break; // Terminar el procesamiento al encontrar una fila vacía
            }

            // Combinar cabeceras con valores de la fila
            $dataRow = array_combine($headers, $rowArray);

            // Validar campos requeridos (excluyendo 'imagen', 'descripcion' y 'dificultad)
            $emptyFields = [];
            foreach ($this->requiredColumns as $column) {
                if ($column !== 'imagen' && $column !== 'dificultad' && $column !== 'descripcion' && empty($dataRow[$column])) {
                    $emptyFields[] = $column;
                }
            }

            if (!empty($emptyFields)) {
                $this->messages[] = "Fila " . ($index + 1) . ": Campos vacíos: " . implode(', ', $emptyFields);
                continue;
            }

            // Procesar la fila
            $this->processRow($dataRow, $index);
        }
    }

    private function findImageByHash($directory, $hash)
    {
        // Obtener todas las imágenes en el directorio
        $images = glob($directory . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);

        // Usar array_map para calcular el hash de cada imagen
        $imagesWithHashes = array_map(function ($image) {
            return [
                'path' => $image,
                'hash' => hash_file('sha256', $image), // Calcular el hash de la imagen
            ];
        }, $images);

        // Filtrar las imágenes que coincidan con el hash proporcionado
        $matchingImages = array_filter($imagesWithHashes, function ($imageWithHash) use ($hash) {
            return $imageWithHash['hash'] === $hash;
        });

        // Devolver solo las rutas de las imágenes que coinciden
        return array_column($matchingImages, 'path');
    }

    protected function processRow($dataRow, $index)
    {
        try {
            // Si solo estamos validando, no procesar la fila
            if ($this->validateOnly) {
                return;
            }

            // Usar el área ID del constructor (no del Excel)
            $areaId = $this->areaId;
            $imagePath = null;
            
            // Obtener la carrera asociada al área
            $area = DB::table('areas')->where('id', $areaId)->first();
            if (!$area) {
                $this->messages[] = "Fila " . ($index + 1) . ": No se encontró el área con ID {$areaId}.";
                return;
            }

            $career = DB::table('careers')->where('id', $area->career_id)->first();
            if (!$career) {
                $this->messages[] = "Fila " . ($index + 1) . ": No se encontró la carrera para el área con ID {$areaId}.";
                return;
            }

            // Obtener la unidad a la que pertenece la carrera
            $unit = DB::table('careers')->where('id', $career->unit_id)->first();
            if (!$unit) {
                $this->messages[] = "Fila " . ($index + 1) . ": No se encontró la unidad para la carrera con ID {$career->id}.";
                return;
            }
            
            $areaName = $area->name;
            $unitSigla = $unit->initials;  // Usar la sigla de la unidad
            $careerSigla = $career->initials; // Usar la sigla de la carrera

            // Procesar la imagen si existe
            if (!empty($dataRow['imagen'])) {
                $imagesDirectory = $this->extractedPath . DIRECTORY_SEPARATOR . 'imagenes';
                
                // Calcular el hash de la imagen
                $imageHash = hash_file('sha256', $imagesDirectory . DIRECTORY_SEPARATOR . $dataRow['imagen']);
               
                // Buscar la imagen en la carpeta
                $originalImagePath = $this->findImageByHash($imagesDirectory, $imageHash);
                
                if (!empty($originalImagePath)) {
                    // Ruta destino usando la sigla de la carrera y el nombre del área
                    $destinationPath = public_path('images' . DIRECTORY_SEPARATOR . 'units' . DIRECTORY_SEPARATOR . $unitSigla . DIRECTORY_SEPARATOR .
                        $careerSigla . DIRECTORY_SEPARATOR . $areaName . DIRECTORY_SEPARATOR . 'questions' . DIRECTORY_SEPARATOR . basename($dataRow['imagen']));
                    
                    // Crear la carpeta si no existe
                    $destinationDirectory = dirname($destinationPath);
                    if (!File::exists($destinationDirectory)) {
                        File::makeDirectory($destinationDirectory, 0777, true, true);
                        $this->messages[] = "Fila " . ($index + 1) . " : Carpeta creada: " . $destinationDirectory;
                    }

                    // Verificar si la imagen ya existe en el destino
                    $matchingImages = $this->findImageByHash($destinationDirectory, $imageHash);
                    if (!empty($matchingImages)) {
                        $this->messages[] = "Fila " . ($index + 1) . ": La imagen '" . basename($dataRow['imagen']) . "' ya existe.";
                        $relativeImagePath = str_replace(public_path(), '', $matchingImages[0]);
                        $imagePath = ltrim($relativeImagePath, '/\\');
                    } else {
                        // Copiar la imagen si no existe en el destino
                        if (File::copy($originalImagePath[0], $destinationPath)) {
                            $imagePath = 'images' . DIRECTORY_SEPARATOR . 'units' . DIRECTORY_SEPARATOR .
                                $unitSigla . DIRECTORY_SEPARATOR . $careerSigla . DIRECTORY_SEPARATOR . $areaName . DIRECTORY_SEPARATOR . 'questions' . DIRECTORY_SEPARATOR . basename($dataRow['imagen']);
                            $this->messages[] = "Fila " . ($index + 1) . ": Imagen copiada con éxito.";
                        } else {
                            $this->messages[] = "Fila " . ($index + 1) . ": No se pudo copiar la imagen.";
                        }
                    }
                } else {
                    $this->messages[] = "Fila " . ($index + 1) . ": No se encontró la imagen '" . basename($dataRow['imagen']) . "'.";
                }
            }
            
            // Buscar si la pregunta ya existe
            $existingQuestion = QuestionBank::where('question', $dataRow['pregunta'])
                ->where('area_id', $areaId)
                ->exists();
            
            if (!$existingQuestion) {
                // Crear la pregunta
                $question = QuestionBank::create([
                    'area_id' => $areaId,
                    'excel_import_id' => $this->excelImportId,
                    'question' => $dataRow['pregunta'],
                    'description' => $dataRow['descripcion'],
                    'difficulty' => $dataRow['dificultad'],
                    'type' => $dataRow['tipo'],
                    'image' => $imagePath,
                    'status' => 'activo',
                ]);
            }

            // Procesar las respuestas
            $answers = $this->processAnswers($question, $dataRow);

            // Registrar la pregunta exitosa
            $this->registeredQuestions[] = [
                'row' => $index + 1,
                'pregunta_id' => $question->id,
                'pregunta' => $dataRow['pregunta'],
                'area' => $areaId,
                'tipo' => $dataRow['tipo'],
                'dificultad' => $dataRow['dificultad'],
                'respuestas' => $answers,
                'imagen' => $imagePath ? 'Sí' : 'No'
            ];

            $this->messages[] = "Fila " . ($index + 1) . ": Pregunta registrada exitosamente.";
        } catch (\Exception $e) {
            // Registrar la pregunta que falló
            $this->skippedQuestions[] = [
                'row' => $index + 1,
                'pregunta' => $dataRow['pregunta'],
                'area' => $dataRow['area'],
                'motivo' => $e->getMessage()
            ];

            $this->messages[] = "Error en fila " . ($index + 1) . ": " . $e->getMessage();
            Log::error("Error procesando fila " . ($index + 1) . ": " . $e->getMessage());
        }
    }


    protected function processAnswers($question, $dataRow)
    {
        $answers = [];

        // Filtrar las columnas que contienen respuestas
        $optionColumns = array_filter(array_keys($dataRow), fn($key) => strpos($key, 'opcion') === 0);

        // Normalizar respuestas correctas para comparación sin errores
        $correctAnswers = isset($dataRow['respuesta correcta'])
            ? array_map('trim', explode(',', strtolower($dataRow['respuesta correcta'])))
            : [];

        foreach ($optionColumns as $optionKey) {
            if (!empty($dataRow[$optionKey])) {
                $answerText = trim($dataRow[$optionKey]);

                // Comparar ignorando mayúsculas/minúsculas y espacios
                $isCorrect = in_array(strtolower($answerText), $correctAnswers);

                AnswerBank::create([
                    'bank_question_id' => $question->id,
                    'answer' => $answerText,
                    'is_correct' => $isCorrect,
                    'status' => 'activo',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $answers[] = [
                    'texto' => $answerText,
                    'correcta' => $isCorrect ? 'Sí' : 'No'
                ];
            }
        }

        return $answers;
    }

    public function getImportSummary()
    {
        return [
            'registradas' => [
                'total' => count($this->registeredQuestions),
                'detalle' => $this->registeredQuestions
            ],
            'no_registradas' => [
                'total' => count($this->skippedQuestions),
                'detalle' => $this->skippedQuestions
            ],
            'duplicadas' => [
                'total' => count($this->duplicateDetails),
                'detalle' => $this->duplicateDetails
            ]
        ];
    }

    public function getMessages()
    {
        return $this->messages;
    }
}
