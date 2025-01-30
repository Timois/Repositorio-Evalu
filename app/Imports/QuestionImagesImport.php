<?php

namespace App\Imports;

use App\Models\AnswerBank;
use App\Models\Areas;
use App\Service\ServiceArea;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;

class QuestionImagesImport implements ToCollection
{
    protected $excelImportId;
    protected $messages = [];
    protected $extractedPath;

    // Definir las columnas requeridas
    protected $requiredColumns = ['area', 'pregunta', 'descripcion', 'tipo', 'imagen', 'nota', 'opcion1', 'opcion2', 'opcion3', 'opcion4', 'respuesta correcta'];

    public function __construct(array $params)
    {
        $this->excelImportId = $params['excel_import_id'];
        $this->extractedPath = $params['extractedPath'];
    }
    // Crear la funcion de validateFormat
    public function validateFormat($data)
    {
        //dd($this->extractedPath);
        $messages = [];

        // Verificar si hay datos
        if (empty($data) || empty($data[0])) {
            return $messages;
        }

        // Obtener las cabeceras
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
        //dd($rows->all());
        // Procesar cada fila de datos
        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Omitir headers

            $rowData = $row->toArray();
            if (count($rowData) !== count($this->requiredColumns)) {
                $this->messages[] = "La fila $index no contiene todas las columnas requeridas.";
                continue;
            }
            $area_archivo=$row[0];
            $data = array_combine($this->requiredColumns, $rowData);

            try {
                // Procesar imagen si existe
                if (!empty($data['imagen'])) {
                    $imagePath = $this->processImage($data['imagen'],$area_archivo);
                    //dd($imagePath);
                    $data['imagen'] = $imagePath; // Guardar la ruta de la imagen en los datos
                }

                // Guardar la pregunta
                $question = $this->saveQuestion($data);

                // Guardar las respuestas
                $answers = $this->saveAnswer($question, $data);
            } catch (\Exception $e) {
                $this->messages[] = "Error en la fila $index: " . $e->getMessage();
            }
        }
    }

    protected function processImage($imageName,$area_archivo)
    {
        $imagePath = $this->extractedPath . 'import/' . $area_archivo . '/imagenes/' . $imageName;
        $destination = public_path('images/questions/' . time() . '_' . basename($imageName));
        
        if (!file_exists($imagePath)) {
            throw new \Exception("Imagen no encontrada en la ruta: $imagePath");
        }
        
        // Mover el archivo a la carpeta `public/images/`
        if (!rename($imagePath, $destination)) {
            throw new \Exception("No se pudo mover la imagen a: $destination");
        }
        
        return 'images/' . basename($destination);
    }

    protected function saveArea($area)
    {
        // Verificar si la area ya existe
        $existingArea = Areas::where('name', $area)->first();
        return $existingArea->id;
    }

    protected function saveQuestion($question)
    {
        // Llamar a la función saveArea para obtener el area_id
        $areaId = $this->saveArea($question['area']);

        $exists = DB::table('excel_imports')->where('id', $this->excelImportId)->exists();
        if (!$exists) {
            return response()->json([
                "status" => "error",
                "messages" => ["El ID de importación de Excel ($this->excelImportId) no existe."]
            ]);
        }
        //dd($this->excelImportId);
        // Si existe el import del excel, entonces devolver el id
        $exists = $this->excelImportId;

        // Preparar los datos para la función SaveQuestion
        $data = [
            'area_id' => $areaId,              // Asignamos el area_id obtenido de saveArea
            'excel_import_id' => $exists,  // Asumiendo que tienes este id de la importación
            'question' => $question['pregunta'],
            'description' => $question['descripcion'],
            'type' => $question['tipo'],
            'image' => $question['imagen'],
            'total_weight' => $question['nota'],
            'status' => 'activo',
        ];

        // Llamar al servicio SaveQuestion para guardar la pregunta
        $newQuestion = ServiceArea::SaveQuestion($data);

        // Ahora puedes retornar la pregunta o hacer algo más con $newQuestion
        return $newQuestion;
    }


    protected function saveAnswer($answer)
    {
        $questionId = $this->saveQuestion($answer['pregunta']);

        $data = [
            'question_bank_id' => $questionId,
            'answer' => $answer['respuesta_correcta'],
            'weight' => $answer['nota'],
            'is_correct' => true,
            'status' => 'active',
        ];

        // Llamar al servicio SaveAnswer para guardar la respuesta
        $newAnswer = ServiceArea::SaveAnswer($data);

        // Ahora puedes retornar la respuesta o hacer algo más con $newAnswer
        return $newAnswer;
    }

    public function getMessages()
    {
        return $this->messages;
    }
}
