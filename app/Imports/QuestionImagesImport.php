<?php

namespace App\Imports;

use App\Models\AnswerBank;
use App\Models\QuestionBank;
use App\Service\ServiceArea;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\File;

class QuestionImagesImport implements ToCollection
{
    protected $excelImportId;
    protected $messages = [];
    protected $extractedPath;

    protected $requiredColumns = [
        'area',
        'pregunta',
        'descripcion',
        'tipo',
        'imagen',
        'nota',
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

            // Validar campos requeridos
            $emptyFields = [];
            foreach ($this->requiredColumns as $column) {
                if ($column !== 'imagen' && empty($dataRow[$column])) {
                    $emptyFields[] = $column;
                }
            }

            if (!empty($emptyFields)) {
                $this->messages[] = "Fila " . ($index + 1) . ": Campos vacíos: " . implode(', ', $emptyFields);
                continue;
            }

            try {
                // Verificar si el área existe
                $areaId = ServiceArea::FindArea($dataRow['area']);
                if (!$areaId) {
                    $this->messages[] = "Fila " . ($index + 1) . ": Área '{$dataRow['area']}' no encontrada.";
                    continue;
                }
                $areaName = $dataRow['area'];
                $imagePath = null; // Inicializar en null por defecto
                
                // Verificar si el campo 'imagen' tiene datos
                if (!empty($dataRow['imagen'])) {
                    // Concatenar el extractedPath con la carpeta area y el nombre de la imagen
                    $originalImagePath = public_path($this->extractedPath . DIRECTORY_SEPARATOR . $areaName . DIRECTORY_SEPARATOR . basename($dataRow['imagen']));
                    // Ruta de destino donde moveremos la imagen
                    $destinationPath = public_path('images' . DIRECTORY_SEPARATOR . 'questions' . DIRECTORY_SEPARATOR . basename($dataRow['imagen']));
                    
                    // Crear la carpeta si no existe
                    if (!File::exists(public_path('images' . DIRECTORY_SEPARATOR . 'questions'))) {
                        File::makeDirectory(public_path('images' . DIRECTORY_SEPARATOR . 'questions'), 0777, true);
                    }
                    
                    // Mover la imagen a la carpeta de destino
                    if (File::move($originalImagePath, $destinationPath)) {
                        $imagePath = asset('images/questions/' . basename($dataRow['imagen']));
                        // Mosstrar el mensaje de éxito
                        $this->messages[] = "Fila " . ($index + 1) . ": Imagen movida con exito: " . basename($dataRow['imagen']);
                    }else{
                        // Mostrar el mensaje de error
                        $this->messages[] = "Fila " . ($index + 1) . ": No se pudo mover la imagen: " . basename($dataRow['imagen']);
                    }
                    
                    
                }
                // Guardar la pregunta
                $question = QuestionBank::create([
                    'area_id' => $areaId,
                    'excel_import_id' => $this->excelImportId,
                    'question' => $dataRow['pregunta'],
                    'description' => $dataRow['descripcion'],
                    'type' => $dataRow['tipo'],
                    'image' => $imagePath,
                    'total_weight' => $dataRow['nota'], 
                    'status' => 'activo',
                ]);

                // Primero, obtener las opciones disponibles
                $optionColumns = array_filter(array_keys($dataRow), function ($key) {
                    return strpos($key, 'opcion') === 0;
                });

                // Obtener el texto de las respuestas correctas del campo 'respuesta correcta'
                $correctAnswerTexts = ($dataRow['tipo'] === 'multiple')
                    ? explode(',', $dataRow['respuesta correcta'])
                    : [$dataRow['respuesta correcta']];

                // Crear un mapeo de las opciones y sus valores
                $optionsMap = [];
                foreach ($optionColumns as $optionKey) {
                    $optionNumber = (int) str_replace('opcion', '', $optionKey);
                    $optionsMap[$optionNumber] = $dataRow[$optionKey];
                }

                // Encontrar los números de las opciones correctas comparando los textos
                $correctAnswers = [];
                foreach ($correctAnswerTexts as $correctText) {
                    foreach ($optionsMap as $number => $text) {
                        if (trim($correctText) === trim($text)) {
                            $correctAnswers[] = $number;
                        }
                    }
                }

                // Procesar las respuestas
                $weightPerAnswer = floatval($dataRow['nota']) / count($correctAnswers);
                $answersToInsert = [];

                foreach ($optionColumns as $optionKey) {
                    $optionNumber = (int) str_replace('opcion', '', $optionKey);

                    if (!empty($dataRow[$optionKey])) {
                        $isCorrect = in_array($optionNumber, $correctAnswers);
                        $answersToInsert[] = [
                            'bank_question_id' => $question->id,
                            'answer' => $dataRow[$optionKey],
                            'is_correct' => $isCorrect,
                            'weight' => $isCorrect ? $weightPerAnswer : 0,
                            'status' => 'activo',
                        ];
                    }
                }
                // Guardar respuestas
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
