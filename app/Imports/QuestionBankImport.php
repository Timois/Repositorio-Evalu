<?php

namespace App\Imports;

use App\Models\AnswerBank;
use App\Models\Areas;
use App\Models\QuestionBank;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Service\ServiceArea;

class QuestionBankImport implements ToCollection
{
    protected $excelImportId;
    protected $messages = [];

    public function __construct($excelImportId)
    {
        $this->excelImportId = $excelImportId;
    }

    public function collection(Collection $rows)
    {
        $headers = [];
        $responseMessages = [];

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                $headers = $row->toArray();
                logger()->info('Cabeceras del archivo:', $headers); // Depurar cabeceras
                continue;
            }

            // Validar cantidad de columnas y contenido
            if (count($headers) !== count($row->toArray())) {
                $responseMessages[] = "Error en la fila " . ($index + 1) . ": La cantidad de columnas no coincide.";
                continue;
            }

            $dataRow = array_combine($headers, $row->toArray());

            // Validar claves importantes
            if (!isset($dataRow['pregunta'], $dataRow['area'], $dataRow['descripcion'], $dataRow['tipo'])) {
                $responseMessages[] = "Error en la fila " . ($index + 1) . ": Datos incompletos.";
                continue;
            }

            try {
                // Verificar si el área existe
                $iafind = ServiceArea::FindArea($dataRow['area']);
                if (!$iafind) {
                    $responseMessages[] = "Error en la fila " . ($index + 1) . ": Área no encontrada.";
                    continue;
                }

                $dataToInsert = [
                    'area_id' => $iafind,
                    'excel_import_id' => $this->excelImportId,
                    'question' => $dataRow['pregunta'],
                    'description' => $dataRow['descripcion'],
                    'type' => $dataRow['tipo'],
                    'image' => $dataRow['imagen'], // Puede ser null
                    'total_weight' => $dataRow['nota'],
                    'status' => 'activo',
                ];

                // Guardar pregunta
                $saveQuest = QuestionBank::create($dataToInsert);

                $respuestasCorrectas = [];
                $notaPorRespuesta = 0;

                // Procesar respuestas
                if ($dataRow['tipo'] === 'multiple') {
                    $respuestasCorrectas = array_map('intval', explode(',', $dataRow['respuesta correcta']));
                    $notaPorRespuesta = floatval($dataRow['nota']) / count($respuestasCorrectas);
                } else {
                    $respuestasCorrectas = [intval($dataRow['respuesta correcta'])];
                    $notaPorRespuesta = floatval($dataRow['nota']);
                }

                $answersToInsert = [];
                for ($i = 1; $i <= 4; $i++) {
                    if (!empty($dataRow["opcion$i"])) {
                        $esCorrecta = in_array($i, $respuestasCorrectas);
                        $answersToInsert[] = [
                            'bank_question_id' => $saveQuest->id, // Usar el ID de la pregunta
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
                    $responseMessages[] = 'Respuestas guardadas exitosamente para la pregunta: ' . $dataRow['pregunta'];
                }
            } catch (\Exception $e) {
                $responseMessages[] = "Error en la fila " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        // Guardar los mensajes en la propiedad para ser accesibles desde el controlador
        $this->messages = $responseMessages;
    }

    // Método para obtener los mensajes
    public function getMessages()
    {
        return $this->messages;
    }
}
