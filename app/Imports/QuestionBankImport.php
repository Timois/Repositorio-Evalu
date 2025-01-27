<?php

namespace App\Imports;

use App\Models\AnswerBank;
use App\Models\QuestionBank;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Service\ServiceArea;

class QuestionBankImport implements ToCollection
{
    protected $excelImportId;
    protected $messages = [];

    // Definir las columnas requeridas
    protected $requiredColumns = [
        'area',
        'pregunta',
        'descripcion',
        'imagen',
        'tipo',
        'nota',
        'opcion1',
        'opcion2',
        'opcion3',
        'opcion4',
        'respuesta correcta'
    ];

    public function __construct($excelImportId)
    {
        $this->excelImportId = $excelImportId;
    }

    public function validateFormat($data)
    {
        $messages = [];

        // Verificar si hay datos
        if (empty($data) || empty($data[0])) {
            return ['El archivo Excel está vacío'];
        }

        // Obtener las cabeceras
        $headers = $data[0][0];

        // Verificar columnas faltantes
        $missingColumns = array_diff($this->requiredColumns, $headers);
        if (!empty($missingColumns)) {
            $messages[] = "Faltan las siguientes columnas requeridas: " . implode(', ', $missingColumns);
        }

        // Verificar columnas extras no permitidas
        $extraColumns = array_diff($headers, $this->requiredColumns);
        if (!empty($extraColumns)) {
            $messages[] = "El archivo contiene columnas no permitidas: " . implode(', ', $extraColumns);
        }

        // Verificar que haya al menos una fila de datos además de las cabeceras
        if (count($data[0]) < 2) {
            $messages[] = "El archivo no contiene datos para importar";
        }

        return $messages;
    }

    public function collection(Collection $rows)
    {
        // $headers = [];
        $responseMessages = [];

        // Verificar si hay filas en el Excel
        if ($rows->isEmpty()) {
            $this->messages[] = "El archivo Excel está vacío.";
            return;
        }

        // Obtener las cabeceras del Excel
        $headers = $rows[0]->toArray();

        foreach ($headers as $key => $value) {
            if ($value !== $this->requiredColumns[$key]) {
                $this->messages[] = "LA COLUMNA {$headers[$key]} No COINCIDE CON LA COLUMNA REQUERIDA {$this->requiredColumns[$key]}";
            }
        }

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
            
            // Crear array asociativo con los datos de la fila
            $dataRow = array_combine($headers, $rowArray);
            // dd($dataRow);

            // Validar que los campos requeridos no estén vacíos (excepto imagen)
            $emptyFields = [];
            foreach ($this->requiredColumns as $column) {
                if ($column !== 'imagen' && empty($dataRow[$column])) {
                    $emptyFields[] = $column;
                }
            }

            if (count($emptyFields) === 0) {
                // dd(($emptyFields));

                $responseMessages[] = "Error en la fila " . ($index + 1) . ": Campos vacíos: " . implode(', ', $emptyFields);
                continue;
            }


            try {

                // Verificar si el área existe
                $iafind = ServiceArea::FindArea($dataRow['area']);
                dd($rows);
                if (!$iafind) {
                    $responseMessages[] = "Error en la fila " . ($index + 1) . ": Área ' {$dataRow['area']} ' no encontrada.";
                    continue;
                }

                try {
                    $dataToInsert = [
                        'area_id' => $iafind,
                        'excel_import_id' => $this->excelImportId,
                        'question' => $dataRow['pregunta'],
                        'description' => $dataRow['descripcion'],
                        'type' => $dataRow['tipo'],
                        'image' => basename($dataRow['imagen']),
                        'total_weight' => $dataRow['nota'],
                        'status' => 'activo',
                    ];

                    //dd($dataToInsert);
                    $saveQuest = QuestionBank::create($dataToInsert);
                    // Verificar si la pregunta ya existe para esta área
                    if($index === 5){
                        //dd($saveQuest);
                    }

                } catch (\Exception $e) {
                    // Manejar cualquier error que pueda ocurrir
                    return [
                        'success' => false,
                        'message' => 'Error al guardar la pregunta: ' . $e->getMessage(),
                        'data' => null
                    ];
                }
                $respuestasCorrectas = [];
                $notaPorRespuesta = 0;

                // Procesar respuestas
                if ($dataRow['tipo'] === 'multiple') {
                    $respuestasCorrectas = array_map('intval', explode(',', $dataRow['respuesta correcta']));
                    $notaPorRespuesta = floatval($dataRow['nota']) / count($respuestasCorrectas);
                } else {
                    array_push($respuestasCorrectas, $dataRow['respuesta correcta']);
                    $notaPorRespuesta = floatval($dataRow['nota']);
                }

                $answersToInsert = [];
                for ($i = 1; $i <= 4; $i++) {
                    if (!empty($dataRow["opcion$i"])) {
                        $esCorrecta = in_array($dataRow["opcion$i"], $respuestasCorrectas);
                        $answersToInsert[] = [
                            'bank_question_id' => $saveQuest->id,
                            'answer' => $dataRow["opcion$i"],
                            'is_correct' => $esCorrecta,
                            'weight' => $esCorrecta ? $notaPorRespuesta : 0,
                            'status' => 'activo',
                        ];
                    }
                }

                // Guardar respuestas masivamente
                if (!empty($answersToInsert)) {
                    AnswerBank::insert($answersToInsert);
                    $responseMessages[] = "Fila " . ($index + 1) . ": Pregunta y respuestas guardadas exitosamente.";
                }
            } catch (\Exception $e) {
                dd($e->getMessage());
                $responseMessages[] = "Error en la fila " . ($index + 1) . ": " . $e->getMessage();
                logger()->error("Error en importación fila " . ($index + 1) . ": " . $e->getMessage());
            }
        }
        // dd($saveQuest);


        $this->messages = $responseMessages;
    }

    public function getMessages()
    {
        return $this->messages;
    }
}
