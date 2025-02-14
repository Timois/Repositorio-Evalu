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

            try {
                // Verificar si el área ya existe o crearla
                $areaId = ServiceArea::FindArea($dataRow['area']);

                if (!$areaId) {
                    $this->messages[] = "Fila " . ($index + 1) . ": No se encontró o creó el área '{$dataRow['area']}'.";
                    continue;
                }

                $areaName = $dataRow['area'];
                $imagePath = null;

                // Verificar si el campo 'imagen' tiene datos
                if (!empty($dataRow['imagen'])) {
                    $originalImagePath = public_path($this->extractedPath . DIRECTORY_SEPARATOR . $areaName . 
                    DIRECTORY_SEPARATOR . basename($dataRow['imagen']));
                    $destinationPath = public_path('images' . DIRECTORY_SEPARATOR . 'questions' . DIRECTORY_SEPARATOR . 
                    'sigla' . DIRECTORY_SEPARATOR . $areaName . DIRECTORY_SEPARATOR . basename($dataRow['imagen']));
                    // Crear la carpeta si no existe
                    if (!File::exists($destinationPath)) {
                        File::makeDirectory(dirname($destinationPath), 0777, true, true);
                    }

                    // Mover la imagen
                    if (File::move($originalImagePath, $destinationPath)) {
                        $imagePath = asset('images/questions/sigla/' . $areaName . '/' . basename($dataRow['imagen']));
                        $this->messages[] = "Fila " . ($index + 1) . ": Imagen movida con éxito: " . basename($dataRow['imagen']);
                    } else {
                        $this->messages[] = "Fila " . ($index + 1) . ": No se pudo mover la imagen: " . basename($dataRow['imagen']);
                    }
                }

                // **Normalizar la pregunta antes de verificar si existe**
                $normalizedQuestion = DB::selectOne("SELECT normalizar_cadena(?) AS normalized", [$dataRow['pregunta']])->normalized;

                // **Verificar si la pregunta ya existe en esa área**
                $questionExists = DB::table('bank_questions')
                    ->where('area_id', $areaId)
                    ->whereRaw('normalizar_cadena(question) = ?', [$normalizedQuestion])
                    ->where('status', 'activo')
                    ->exists();

                if ($questionExists) {
                    $this->messages[] = "Fila " . ($index + 1) . ": La pregunta '{$dataRow['pregunta']}' ya existe en esta área.";
                    continue;
                }

                // **Guardar la nueva pregunta**
                $question = QuestionBank::create([
                    'area_id' => $areaId,
                    'excel_import_id' => $this->excelImportId,
                    'question' => $dataRow['pregunta'],
                    'question_normalized' => $normalizedQuestion, // Guardar versión normalizada
                    'description' => $dataRow['descripcion'],
                    'type' => $dataRow['tipo'],
                    'image' => $imagePath,
                    'status' => 'activo',
                ]);

                // **Procesar respuestas**
                $optionColumns = array_filter(array_keys($dataRow), fn($key) => strpos($key, 'opcion') === 0);
                $correctAnswerTexts = ($dataRow['tipo'] === 'multiple') ? explode(',', $dataRow['respuesta correcta']) : [$dataRow['respuesta correcta']];

                // Mapeo de opciones
                $optionsMap = [];
                foreach ($optionColumns as $optionKey) {
                    $optionNumber = (int) str_replace('opcion', '', $optionKey);
                    $optionsMap[$optionNumber] = $dataRow[$optionKey];
                }

                // Determinar respuestas correctas
                $correctAnswers = [];
                foreach ($correctAnswerTexts as $correctText) {
                    foreach ($optionsMap as $number => $text) {
                        if (trim($correctText) === trim($text)) {
                            $correctAnswers[] = $number;
                        }
                    }
                }

                // Insertar respuestas
                $answersToInsert = [];
                foreach ($optionColumns as $optionKey) {
                    $optionNumber = (int) str_replace('opcion', '', $optionKey);
                    if (!empty($dataRow[$optionKey])) {
                        $isCorrect = in_array($optionNumber, $correctAnswers);
                        $answersToInsert[] = [
                            'bank_question_id' => $question->id,
                            'answer' => $dataRow[$optionKey],
                            'is_correct' => $isCorrect,
                            'status' => 'activo',
                        ];
                    }
                }

                // Guardar respuestas en la base de datos
                if (!empty($answersToInsert)) {
                    AnswerBank::insert($answersToInsert);
                    $this->messages[] = "Fila " . ($index + 1) . ": Pregunta y respuestas guardadas exitosamente.";
                }
            } catch (\Exception $e) {
                $this->messages[] = "Fila " . ($index + 1) . ": Error al guardar - " . $e->getMessage();
                logger()->error("Error en fila " . ($index + 1) . ": " . $e->getMessage());
            }
        }
    }

    public function getMessages()
    {
        return $this->messages;
    }
}
