<?php

namespace App\Imports;

use App\Models\AnswerBank;
use App\Models\QuestionBank;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;

class QuestionBankImport implements ToCollection
{
    protected $excelImportId;
    protected $areaId;
    protected $messages = [];

    // Contadores
    protected $countTotal = 0;
    protected $countInserted = 0;
    protected $countRepeated = 0;

    // Repetidas para archivo
    protected $repeatedRows = [];

    protected $requiredColumns = [
        'pregunta',
        'descripcion',
        'dificultad',
        'imagen',   
        'tipo',
        'opcion1',
        'opcion2',
        'opcion3',
        'opcion4',
        'respuesta correcta'
    ];

    public function __construct($excelImportId, $areaId)
    {
        $this->excelImportId = $excelImportId;
        $this->areaId = $areaId;
    }

    public function validateFormat($data)
    {
        $messages = [];

        if (empty($data) || empty($data[0])) {
            return ['El archivo Excel está vacío'];
        }

        $headers = $data[0][0];
        $missingColumns = array_diff($this->requiredColumns, $headers);
        if (!empty($missingColumns)) {
            $messages[] = "Faltan columnas: " . implode(', ', $missingColumns);
        }

        $extraColumns = array_diff($headers, $this->requiredColumns);
        if (!empty($extraColumns)) {
            $messages[] = "Columnas no permitidas: " . implode(', ', $extraColumns);
        }

        if (count($data[0]) < 2) {
            $messages[] = "El archivo no contiene datos para importar";
        }

        return $messages;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            $this->messages[] = "El archivo Excel está vacío.";
            return;
        }

        $headers = $rows[0]->toArray();
        $responseMessages = [];

        $headersDiff = array_diff($this->requiredColumns, $headers);
        if (!empty($headersDiff)) {
            $this->messages[] = "Columnas faltantes: " . implode(', ', $headersDiff);
            return;
        }

        foreach ($rows as $index => $row) {
            if ($index === 0) continue;

            $this->countTotal++;
            $rowArray = $row->toArray();

            if (empty(array_filter($rowArray, fn($value) => $value !== null && $value !== ''))) {
                break;
            }

            if (count($headers) !== count($rowArray)) {
                $responseMessages[] = "Error en la fila " . ($index + 1) . ": número de columnas no coincide.";
                continue;
            }

            $dataRow = array_combine($headers, $rowArray);

            $emptyFields = [];
            foreach ($this->requiredColumns as $column) {
                if (!in_array($column, ['imagen', 'dificultad', 'descripcion']) && empty($dataRow[$column])) {
                    $emptyFields[] = $column;
                }
            }

            if (count($emptyFields) > 0) {
                $responseMessages[] = "Fila " . ($index + 1) . ": Campos vacíos: " . implode(', ', $emptyFields);
                continue;
            }

            // 🛑 Verificar si la pregunta ya existe
            $exists = QuestionBank::where('area_id', $this->areaId)
                ->where('question', trim($dataRow['pregunta']))
                ->exists();

            if ($exists) {
                $this->countRepeated++;
                $this->repeatedRows[] = $dataRow;
                $responseMessages[] = "Fila " . ($index + 1) . ": Pregunta repetida, no se insertó.";
                continue;
            }

            try {
                $dataToInsert = [
                    'area_id' => $this->areaId,
                    'excel_import_id' => $this->excelImportId,
                    'question' => $dataRow['pregunta'],
                    'description' => $dataRow['descripcion'],
                    'type' => $dataRow['tipo'],
                    'image' => basename($dataRow['imagen']),
                    'status' => 'activo',
                ];

                $saveQuest = QuestionBank::create($dataToInsert);
                $this->countInserted++;

                $respuestasCorrectas = [];
                if ($dataRow['tipo'] === 'multiple') {
                    $respuestasCorrectas = array_map('intval', explode(',', $dataRow['respuesta correcta']));
                } else {
                    $respuestasCorrectas[] = $dataRow['respuesta correcta'];
                }

                $answersToInsert = [];
                for ($i = 1; $i <= 4; $i++) {
                    if (!empty($dataRow["opcion$i"])) {
                        $esCorrecta = in_array($dataRow["opcion$i"], $respuestasCorrectas);
                        $answersToInsert[] = [
                            'bank_question_id' => $saveQuest->id,
                            'answer' => $dataRow["opcion$i"],
                            'is_correct' => $esCorrecta,
                            'status' => 'activo',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if (!empty($answersToInsert)) {
                    AnswerBank::insert($answersToInsert);
                }

                $responseMessages[] = "Fila " . ($index + 1) . ": Pregunta y respuestas guardadas.";
            } catch (\Exception $e) {
                $responseMessages[] = "Fila " . ($index + 1) . ": Error al guardar. " . $e->getMessage();
            }
        }

        // Generar resumen
        $responseMessages[] = "Resumen: Total procesadas: {$this->countTotal}, Insertadas: {$this->countInserted}, Repetidas: {$this->countRepeated}";

        // Generar archivo CSV de repetidas (si hay)
        if (count($this->repeatedRows) > 0) {
            $filename = 'repetidas_' . now()->format('Ymd_His') . '.csv';
            $filePath = storage_path("app/public/{$filename}");
            $fp = fopen($filePath, 'w');

            fputcsv($fp, $this->requiredColumns); // encabezado
            foreach ($this->repeatedRows as $row) {
                fputcsv($fp, array_map(fn($key) => $row[$key] ?? '', $this->requiredColumns));
            }

            fclose($fp);

            $responseMessages[] = [
                'success' => false,
                'download_repetidas' => asset("storage/{$filename}"),
                'message' => 'Algunas preguntas estaban repetidas. Puedes descargar el listado.'
            ];
        }

        $this->messages = $responseMessages;
    }

    public function getMessages()
    {
        return $this->messages;
    }
}
