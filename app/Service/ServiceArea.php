<?php

namespace App\Service;

use App\Models\Areas;
use App\Models\QuestionBank;
use App\Models\AnswerBank;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceArea
{
    // Buscar área por nombre o crearla si no existe
    public static function FindArea($name)
    {
        $area = Areas::where('name', $name)->first();

        if (!$area) {
            $area = new Areas();
            $area->name = $name;
            $area->description = $name;
            $area->save();
        }
        //dd($area);
        return $area->id;
    }

    // Guardar la pregunta
    public static function SaveQuestion($data)
    {
        //dd($data);
        $question = new QuestionBank();
        $question->area_id = $data['area_id'];
        $question->excel_import_id = $data['excel_import_id'];
        $question->question = $data['question'];
        $question->description = $data['description'];
        $question->type = $data['type'];
        $question->image = $data['image'];
        $question->total_weight = $data['total_weight'];
        $question->status = $data['status'];
        $question->save();
        //dd($question);

        return $question;
    }
    // Guardar respuesta
    public static function SaveAnswer($data)
    {
        $answer = new AnswerBank();
        $answer->bank_question_id = $data['bank_question_id'];
        $answer->answer = $data['answer'];
        $answer->weight = $data['weight'];
        $answer->is_correct = $data['is_correct'];
        $answer->status = $data['status'];
        $answer->save();

        return $answer;
    }

    // Función para manejar la inserción de preguntas y respuestas en una transacción
    public static function SaveQuestionsAndAnswers($dataToInsert, $answersToInsert)
    {
        DB::beginTransaction();

        try {
            // Insertar preguntas
            $questionIds = [];
            foreach ($dataToInsert as $questionData) {
                $question = self::SaveQuestion($questionData);
                $questionIds[] = $question->id;
            }

            // Insertar respuestas
            foreach ($answersToInsert as $answerData) {
                // Se asegura de que la respuesta se relacione con la pregunta correcta
                $answerData['bank_question_id'] = $questionIds[$answerData['bank_question_id']];
                self::SaveAnswer($answerData);
            }

            // Confirmar la transacción si todo se inserta correctamente
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en la inserción de preguntas y respuestas: ' . $e->getMessage());
            return false;
        }
    }
}
