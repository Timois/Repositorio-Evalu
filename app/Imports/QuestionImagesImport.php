<?php

namespace App\Imports;

use App\Models\AcademicManagementCareer;
use App\Models\AcademicManagementPeriod;
use App\Models\AnswerBank;
use App\Models\Career;
use App\Models\Evaluation;
use App\Models\QuestionBank;
use App\Models\QuestionEvaluation;
use App\Service\ServiceArea;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Question\Question;

class QuestionImagesImport implements ToCollection
{
    protected $excelImportId;
    protected $messages = [];
    protected $extractedPath;
    protected $sigla;
    protected $registeredQuestions = [];
    protected $skippedQuestions = [];
    protected $processedRows = [];
    protected $duplicateDetails = [];

    protected $requiredColumns = [
        'area',
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
        $this->sigla = $params['sigla'];
    }

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
                logger()->info('Fila ' . ($index + 1) . ' está vacía, terminando el procesamiento.');
                break; // Terminar el procesamiento al encontrar una fila vacía
            }

            // Combinar cabeceras con valores de la fila
            $dataRow = array_combine($headers, $rowArray);

            // Validar campos requeridos (excluyendo 'imagen' y 'dificultad)
            $emptyFields = [];
            foreach ($this->requiredColumns as $column) {
                if ($column !== 'imagen' && $column !== 'dificultad' && empty($dataRow[$column])) {
                    $emptyFields[] = $column;
                }
            }

            if (!empty($emptyFields)) {
                $this->messages[] = "Fila " . ($index + 1) . ": Campos vacíos: " . implode(', ', $emptyFields);
                continue;
            }
        }
    }

    protected function processRow($dataRow, $index)
    {
        try {
            $areaId = ServiceArea::FindArea($dataRow['area']);
            $normalizedQuestion = DB::selectOne(
                "SELECT normalizar_cadena(?) AS normalized",
                [$dataRow['pregunta']]
            )->normalized;
            $areaName = $dataRow['area'];
            $imagePath = null;
            // Procesar la imagen si existe
            $imagePath = null;
            if (!empty($dataRow['imagen'])) {
                $originalImagePath = public_path($this->extractedPath . DIRECTORY_SEPARATOR . $areaName .
                    DIRECTORY_SEPARATOR . basename($dataRow['imagen']));
                $destinationPath = public_path('images' . DIRECTORY_SEPARATOR . 'questions' . DIRECTORY_SEPARATOR .
                    $this->sigla . DIRECTORY_SEPARATOR . $areaName . DIRECTORY_SEPARATOR . basename($dataRow['imagen']));
                // Crear la carpeta si no existe
                $destinationDirectory = dirname($destinationPath);
                if (!File::exists($destinationDirectory)) {
                    File::makeDirectory($destinationDirectory, 0777, true, true);
                    $this->messages[] = "Fila " . ($index + 1) . ": Se creó el directorio para la sigla '{$this->sigla}' y área '{$areaName}'.";
                } else {
                    // Si ya existe el directorio, simplemente lo informamos
                    $this->messages[] = "Fila " . ($index + 1) . ": Se usará el directorio existente para la sigla '{$this->sigla}' y área '{$areaName}'.";
                }

                // Mover la imagen
                if (File::exists($originalImagePath)) {
                    if (File::move($originalImagePath, $destinationPath)) {
                        $imagePath = asset('images/questions/' . $this->sigla . '/' . $areaName . '/' . basename($dataRow['imagen']));
                        $this->messages[] = "Fila " . ($index + 1) . ": Imagen movida con éxito: " . basename($dataRow['imagen']);
                    } else {
                        $this->messages[] = "Fila " . ($index + 1) . ": No se pudo mover la imagen: " . basename($dataRow['imagen']);
                    }
                } else {
                    $this->messages[] = "Fila " . ($index + 1) . ": No se encontró la imagen original: " . basename($dataRow['imagen']);
                }
            }

            // Crear la pregunta
            $question = QuestionBank::create([
                'area_id' => $areaId,
                'excel_import_id' => $this->excelImportId,
                'question' => $dataRow['pregunta'],
                'question_normalized' => $normalizedQuestion,
                'description' => $dataRow['descripcion'],
                'difficulty' => $dataRow['dificultad'],
                'type' => $dataRow['tipo'],
                'image' => $imagePath,
                'status' => 'activo',
            ]);

            // Procesar las respuestas
            $answers = $this->processAnswers($question, $dataRow);

            // Registrar la pregunta exitosa
            $this->registeredQuestions[] = [
                'row' => $index + 1,
                'pregunta_id' => $question->id,
                'pregunta' => $dataRow['pregunta'],
                'area' => $dataRow['area'],
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
