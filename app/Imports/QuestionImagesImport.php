<?php

namespace App\Imports;

use App\Models\AnswerBank;
use App\Models\Areas;
use App\Service\ServiceArea;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;

class QuestionImagesImport implements ToCollection
{
    protected $excelImportId;
    protected $messages = [];
    protected $extractedPath;

    protected $requiredColumns = ['area', 'pregunta', 'descripcion', 'tipo', 'imagen', 'nota', 'opcion1', 'opcion2', 'opcion3', 'opcion4', 'respuesta correcta'];

    public function __construct(array $params)
    {
        $this->excelImportId = $params['excel_import_id'];
        $this->extractedPath = $params['extractedPath'];
        //dd($this->extractedPath);
    }

    public function validateFormat($data)
    {
        
        $messages = [];

        if (empty($data) || empty($data[0])) {
            $messages[] = "Error: El archivo está vacío o no tiene encabezados.";
            return $messages;
        }

        $headers = $data[0];

        // Validar que todas las columnas requeridas existan        
        $missingColumns = array_diff($this->requiredColumns, $headers);
        if (!empty($missingColumns)) {
            $messages[] = "Error: Faltan las siguientes columnas requeridas: " . implode(', ', $missingColumns);
        }

        return $messages;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            $this->messages[] = "El archivo Excel está vacío.";
            return;
        }

        // Obtener y validar headers
        $headers = $rows->first()->toArray();
        $formatMessages = $this->validateFormat(['0' => $headers]);
        if (!empty($formatMessages)) {
            $this->messages = array_merge($this->messages, $formatMessages);
            return;
        }

        // Crear un array asociativo con los índices de las columnas
        $columnIndexes = array_flip($headers);

        // Procesar cada fila de datos
        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Omitir headers

            try {
                $rowData = $row->toArray();

                // Verificar que la fila tiene suficientes columnas
                if (count($rowData) < count($this->requiredColumns)) {
                    $this->messages[] = "La fila $index no contiene todas las columnas requeridas.";
                    continue;
                }

                // Crear array asociativo con los datos de la fila
                $data = [];
                foreach ($this->requiredColumns as $column) {
                    $columnIndex = $columnIndexes[$column] ?? null;
                    if ($columnIndex === null) {
                        throw new \Exception("Columna '$column' no encontrada");
                    }
                    $data[$column] = $rowData[$columnIndex] ?? null;
                }

                $area_archivo = $data['area'];
                // Procesar imagen si existe
                if (($data['imagen'])) {
                    
                    //if($index === 2) dd($data['imagen']);
                    $imagePath = $this->processImage($data['imagen'], $area_archivo);
                    $data['imagen'] = $imagePath;
                }
                
                //Guardar area
                $areaId = $this->saveArea($data['area']);

                // Guardar la pregunta
                $question = $this->saveQuestion($data);
                
                // Guardar las respuestas
                $answers = $this->saveAnswers($question, $data);
            } catch (\Exception $e) {
                $this->messages[] = "Error en la fila $index: " . $e->getMessage();
            }
        }
    }

    protected function saveArea($area)
    {
        $existingArea = Areas::where('name', $area)->first();
        if (!$existingArea) {
            throw new \Exception("Área '$area' no encontrada");
        }
        return $existingArea->id;
    }

    protected function processImage($imagePath, $area_archivo)
    {
        //dd($area_archivo);
        if (empty($imagePath)) {
            return null;
        }
        //dd($area_archivo);
        try {
            // Obtener el nombre original del archivo de la ruta completa
            $originalName = basename($imagePath);
            
            // Crear el directorio de destino en public si no existe
            $destinationDir = public_path('images'. DIRECTORY_SEPARATOR .'questions');

            if (!file_exists($destinationDir)) {
                mkdir($destinationDir, 0777, true);
            }
            
            // Usar el nombre original para el archivo de destino
            $destinationPath = $destinationDir . DIRECTORY_SEPARATOR . $originalName;

            // Construir la ruta del archivo de origen
            $sourcePath = $this->extractedPath . DIRECTORY_SEPARATOR . $area_archivo . DIRECTORY_SEPARATOR . 'imagenes' . DIRECTORY_SEPARATOR . $originalName;
            //dd($sourcePath);
            // Verificar si el archivo existe
            if (!file_exists($sourcePath)) {
                throw new \Exception("Imagen no encontrada en la ruta: $sourcePath");
            }

            // Copiar la imagen al directorio public
            if (!copy($sourcePath, $destinationPath)) {
                throw new \Exception("No se pudo copiar la imagen a: $destinationPath");
            }

            // Devolver la ruta relativa con el nombre original
            return 'images'. DIRECTORY_SEPARATOR .'questions' . DIRECTORY_SEPARATOR . $originalName;
        } catch (\Exception $e) {
            $this->messages[] = "Error procesando imagen: " . $e->getMessage();
            return null;
        }
    }

    protected function saveQuestion($data)
    {
        //dd($data);
        $areaId = $this->saveArea($data['area']);

        $exists = DB::table('excel_imports')->where('id', $this->excelImportId)->exists();
        if (!$exists) {
            throw new \Exception("El ID de importación de Excel ($this->excelImportId) no existe.");
        }

        //dd(!empty(public_path($data['imagen']))); 

        // Procesar la imagen y obtener la ruta relativa
        $imagePath = !empty(public_path($data['imagen'])) ?
            $this->processImage(public_path($data['imagen']), $data['area']) :
            null;
        //dd($imagePath);
        // Guardar la pregunta con la ruta de la imagen
        return ServiceArea::SaveQuestion([
            'area_id' => $areaId,
            'excel_import_id' => $this->excelImportId,
            'question' => $data['pregunta'],
            'description' => $data['descripcion'],
            'type' => $data['tipo'],
            'image' => $imagePath, // Ruta relativa de la imagen
            'total_weight' => $data['nota'],
            'status' => 'activo',
        ]);
    }

    protected function saveAnswers($question, $answers)
    {
        if (!$question || !isset($question->type)) {
            throw new Exception("La pregunta no existe o no tiene un tipo definido.");
        }

        $isMultipleChoice = $question->type === 'multiple';

        // Validar que 'nota' sea numérica
        if (!isset($answers['nota']) || !is_numeric($answers['nota'])) {
            throw new Exception("El valor de 'nota' debe ser un número válido.");
        }

        // Convertir respuestas correctas a un array si vienen en un string separado por comas
        $correctAnswers = isset($answers['respuesta correcta'])
            ? (is_array($answers['respuesta correcta'])
                ? $answers['respuesta correcta']
                : explode(',', $answers['respuesta correcta']))
            : [];

        // Limpiar espacios en respuestas correctas
        $correctAnswers = array_map('trim', $correctAnswers);

        // Calcular peso por respuesta correcta si es de opción múltiple
        $weightPerAnswer = $isMultipleChoice
            ? ($answers['nota'] / max(1, count($correctAnswers)))
            : $answers['nota'];

        // Guardar todas las opciones (opcion1 - opcion4)
        foreach (['opcion1', 'opcion2', 'opcion3', 'opcion4'] as $opcion) {
            if (!empty($answers[$opcion])) {
                $isCorrect = in_array(trim($answers[$opcion]), $correctAnswers, true);
                $weight = $isCorrect ? $weightPerAnswer : 0;

                // Guardar en la base de datos
                ServiceArea::SaveAnswer([
                    'bank_question_id' => $question->id,
                    'answer' => trim($answers[$opcion]),
                    'weight' => $weight,
                    'is_correct' => $isCorrect,
                    'status' => 'activo',
                ]);
            }
        }
    }


    public function getMessages()
    {
        return $this->messages;
    }
}
