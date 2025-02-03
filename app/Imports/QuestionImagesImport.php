<?php

namespace App\Imports;

use App\Models\AnswerBank;
use App\Models\QuestionBank;
use App\Service\ServiceArea;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

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

                // Si el campo 'imagen' está vacío, no se guarda el path de la imagen
                $imagePath = null;
                if (!empty($dataRow['imagen'])) {
                    $imageFullPath = public_path('images' . DIRECTORY_SEPARATOR . 'questions' . DIRECTORY_SEPARATOR . basename($dataRow['imagen']));

                    // Verificar si el archivo existe antes de asignarlo
                    if (file_exists($imageFullPath)) {
                        $imagePath = $imageFullPath;
                    } else {
                        $this->messages[] = "Fila " . ($index + 1) . ": La imagen especificada no existe en el sistema.";
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

                // Procesar respuestas
                $correctAnswers = ($dataRow['tipo'] === 'multiple')
                    ? array_map('intval', explode(',', $dataRow['respuesta correcta']))
                    : [intval($dataRow['respuesta correcta'])];

                $weightPerAnswer = floatval($dataRow['nota']) / count($correctAnswers);

                $answersToInsert = [];
                for ($i = 1; $i <= 4; $i++) {
                    if (!empty($dataRow["opcion$i"])) {
                        $isCorrect = in_array($i, $correctAnswers);
                        $answersToInsert[] = [
                            'bank_question_id' => $question->id,
                            'answer' => $dataRow["opcion$i"],
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
