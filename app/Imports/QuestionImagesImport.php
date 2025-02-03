<?php

namespace App\Imports;

use App\Http\Controllers\AreaController;
use App\Models\AnswerBank;
use App\Models\Areas;
use App\Service\ServiceArea;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        // Validar que todas las columnas requeridas existan        
        $missingColumns = array_diff($this->requiredColumns, $data);

        if (count($missingColumns) > 0) {
            $messages[] = "Error: Faltan las siguientes columnas requeridas: " . implode(', ', $missingColumns);
            //dd(count($missingColumns ));   
        }

        return $messages;
    }

    public function collection(Collection $rows)
    {
        // Limpiar los arrays cuyos campos sean todos null
        $rows = $rows->filter(function ($row) {
            return count(array_filter($row->toArray())) > 0;
        });

        // Obtener y validar headers
        $headers = $rows->first()->toArray();

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

            $area_archivo = $dataRow['area'];

            if (($dataRow['imagen'])) {
                $imagePath = $this->processImage($dataRow['imagen'], $area_archivo);
            }

            //Guardar area
            $area = $this->saveArea($area_archivo);
            // dd($imagePath);

            $question = $this->saveQuestion($dataRow, $imagePath, $area->id);

            // Guardar las respuestas
            $this->saveAnswers($question, $dataRow);
        }
    }

    protected function saveArea($area)
    {
        // Limpiar el nombre del área eliminando espacios extras
        $areaName = trim($area);

        // Usar el servicio existente para encontrar o crear el área
        $area = ServiceArea::FindArea($areaName);
        // Guardar area si no existe
        if (!$area) {
            $area = ServiceArea::SaveArea(['name' => $areaName, 'description' => $areaName]);
        }
        return $area;
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
            //dd($originalName);
            // Crear el directorio de destino en public si no existe
            $destinationDir = public_path('images' . DIRECTORY_SEPARATOR . 'questions');

            if (!file_exists($destinationDir)) {
                mkdir($destinationDir, 0777, true);
            }

            // Usar el nombre original para el archivo de destino
            $destinationPath = $destinationDir . DIRECTORY_SEPARATOR . $originalName;

            // Construir la ruta del archivo de origen
            $sourcePath = public_path($this->extractedPath . DIRECTORY_SEPARATOR . $area_archivo . DIRECTORY_SEPARATOR . 'imagenes' . DIRECTORY_SEPARATOR . $originalName);
            // Verificar si el archivo existe
            if (!file_exists($sourcePath)) {

                throw new \Exception("Imagen no encontrada en la ruta: $sourcePath");
            }

            // Copiar la imagen al directorio public
            if (!copy($sourcePath, $destinationPath)) {
                throw new \Exception("No se pudo copiar la imagen a: $destinationPath");
            }

            // Devolver la ruta relativa con el nombre original
            return 'images' . DIRECTORY_SEPARATOR . 'questions' . DIRECTORY_SEPARATOR . $originalName;
        } catch (\Exception $e) {
            $this->messages[] = "Error procesando imagen: " . $e->getMessage();
            return null;
        }
    }

    protected function saveQuestion($data, $imagePath, $area_id)
    {
        $exists = DB::table('excel_imports')->where('id', $this->excelImportId)->exists();
        if (!$exists) {
            throw new \Exception("El ID de importación de Excel ($this->excelImportId) no existe.");
        }

        return ServiceArea::SaveQuestion([
            'area_id' => $area_id,
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
