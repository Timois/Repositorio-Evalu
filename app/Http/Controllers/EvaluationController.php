<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationAssignQuestion;
use App\Http\Requests\ValidationEvaluation;
use App\Models\Evaluation;
use App\Models\QuestionBank;
use App\Models\QuestionEvaluation;
use Illuminate\Http\Request;
use Symfony\Component\Console\Question\Question;

class EvaluationController extends Controller
{
    public function find()
    {
        $evaluation = Evaluation::orderBy('id', 'ASC')->get();
        return response()->json($evaluation);
    }

    public function create(ValidationEvaluation $request)
    {
        $evaluation = new Evaluation();
        $evaluation->title = $request->title;
        $evaluation->description = $request->description;
        $evaluation->number_questions = $request->number_questions;
        $evaluation->total_score = $request->total_score;
        $evaluation->is_random = $request->is_random;
        $evaluation->duration = $request->duration;
        $evaluation->status = $request->status;
        $evaluation->type = $request->type;
        $evaluation->academic_management_period_id = $request->academic_management_period_id;
        $evaluation->save();
        return response()->json($evaluation);
    }

    public function findAndUpdate(ValidationEvaluation $request, string $id)
    {
        try {
            // Busca la evaluación por su ID
            $evaluation = Evaluation::findOrFail($id);

            // Captura los datos del request, solo los campos editables
            $updateData = $request->only([
                'title',
                'description',
                'number_questions',
                'is_random',
                'duration'
            ]);

            // Convierte 'title' a mayúsculas si está presente
            if (isset($updateData['title'])) {
                $updateData['title'] = strtoupper($updateData['title']);
            }

            // Convierte 'description' a minúsculas si está presente
            if (isset($updateData['description'])) {
                $updateData['description'] = strtolower($updateData['description']);
            }

            // Actualiza los datos en el modelo
            $evaluation->update($updateData);

            // Retorna la respuesta exitosa
            return response()->json([
                'message' => 'Evaluación actualizada exitosamente',
                'data' => $evaluation
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => "La evaluación con id: $id no existe."
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la evaluación',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function findById(string $id)
    {
        $evaluation = Evaluation::find($id);
        if (!$evaluation)
            return ["message:", "La evaluación con el id:" . $id . " no existe."];
        return response()->json($evaluation);
    }

    public function AssignQuestion(Request $request)
    {
        try {
            // Obtener la evaluación existente
            $evaluation = Evaluation::findOrFail($request->evaluation_id);

            // Verificar que la evaluación permita preguntas aleatorias
            if (!$evaluation->is_random) {
                return response()->json([
                    'message' => 'Esta evaluación no permite preguntas aleatorias'
                ], 400);
            }

            // Obtener preguntas aleatorias según el número especificado en la evaluación
            $randomQuestions = QuestionBank::query()
                ->inRandomOrder()
                ->take($evaluation->number_questions)
                ->get();

            // Asignar cada pregunta a la evaluación
            foreach ($randomQuestions as $question) {
                QuestionEvaluation::create([
                    'evaluation_id' => $request->evaluation_id,
                    'bank_question_id' => $question->id
                ]);
            }

            return response()->json([
                'message' => 'Preguntas asignadas con éxito',
                'questions_assigned' => $randomQuestions->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al asignar preguntas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function ListAssignedQuestions(Request $request)
    {
        $questions = QuestionEvaluation::get();
        return response()->json($questions);
    }
}
