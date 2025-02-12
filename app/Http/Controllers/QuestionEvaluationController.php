<?php

namespace App\Http\Controllers;

use App\Models\QuestionBank;
use App\Models\QuestionEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Question\Question;

class QuestionEvaluationController extends Controller
{
    // Método para crear una QuestionEvaluation sin score
    public function create(Request $request)
    {
        $request->validate([
            'question_bank_id' => 'required|exists:bank_questions,id',
            'evaluation_id' => 'required|exists:evaluations,id',
        ]);

        $questionEvaluation = new QuestionEvaluation();
        $questionEvaluation->question_bank_id = $request->question_bank_id;
        $questionEvaluation->evaluation_id = $request->evaluation_id;
        // El score se dejará en null hasta que se asigne después
        $questionEvaluation->save();

        return response()->json($questionEvaluation);
    }

    // Método para asignar automáticamente preguntas por área
    public function assignRandomQuestions(Request $request)
    {
        // Validar la solicitud
        $request->validate([
            'evaluation_id' => 'required|exists:evaluations,id',
            'areas' => 'required|array',
            'areas.*.area_id' => 'required|exists:areas,id',
            'areas.*.num_questions' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->areas as $area) {
                // Obtener preguntas aleatorias del banco para esta área
                $randomQuestions = QuestionBank::where('area_id', $area['area_id'])
                    ->inRandomOrder()
                    ->limit($area['num_questions'])
                    ->get();

                // Crear QuestionEvaluation para cada pregunta seleccionada (sin score)
                foreach ($randomQuestions as $question) {
                    $questionEvaluation = new QuestionEvaluation();
                    $questionEvaluation->question_bank_id = $question->id;
                    $questionEvaluation->evaluation_id = $request->evaluation_id;
                    $questionEvaluation->area_id = $area['area_id']; // Agregamos el área_id para referencia
                    $questionEvaluation->save();
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Preguntas asignadas exitosamente',
                'evaluation_id' => $request->evaluation_id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al asignar preguntas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function listAssignedQuestions(Request $request)
    {
        $questions = QuestionEvaluation::where('evaluation_id', $request->evaluation_id)->get();
        return response()->json($questions);
    }

    // Método para asignar puntajes por área
    public function assignScores(Request $request)
    {
        $request->validate([
            'evaluation_id' => 'required|exists:evaluations,id',
            'area_scores' => 'required|array',
            'area_scores.*.area_id' => 'required|exists:areas,id',
            'area_scores.*.score' => 'required|numeric|min:0'
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->area_scores as $areaScore) {
                // Obtener todas las preguntas de esta área en la evaluación
                $questions = QuestionEvaluation::where('evaluation_id', $request->evaluation_id)
                    ->where('area_id', $areaScore['area_id'])
                    ->get();

                $questionsCount = $questions->count();
                if ($questionsCount > 0) {
                    // Distribuir el puntaje equitativamente entre las preguntas del área
                    $scorePerQuestion = $areaScore['score'] / $questionsCount;

                    foreach ($questions as $question) {
                        $question->score = $scorePerQuestion;
                        $question->save();
                    }
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Puntajes asignados exitosamente',
                'evaluation_id' => $request->evaluation_id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al asignar puntajes: ' . $e->getMessage()
            ], 500);
        }
    }

    // Método para actualizar el puntaje de una pregunta específica
    public function assignScore(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:question_evaluations,id',
            'score' => 'required|numeric|min:0'
        ]);

        $questionEvaluation = QuestionEvaluation::findOrFail($request->id);
        $questionEvaluation->score = $request->score;
        $questionEvaluation->save();

        return response()->json($questionEvaluation);
    }
}
