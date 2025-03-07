<?php

namespace App\Http\Controllers;

use App\Models\QuestionEvaluation;
use App\Models\QuestionBank;
use App\Models\Evaluation;
use App\Http\Requests\ValidationQuestionEvaluation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class QuestionEvaluationController extends Controller
{
    public function CantidadPreguntas(Request $request)
    {
        // Validación para asegurar que al menos uno de los campos tenga valor
        $request->validate([
            'cantidadFacil' => 'sometimes|integer|min:0',
            'cantidadMedia' => 'sometimes|integer|min:0',
            'cantidadDificil' => 'sometimes|integer|min:0',
        ]);

        // Verificar que al menos uno de los parámetros esté presente
        if (!$request->has('cantidadFacil') && !$request->has('cantidadMedia') && !$request->has('cantidadDificil')) {
            return response()->json(['error' => 'Al menos uno de los parámetros (cantidadFacil, cantidadMedia, cantidadDificil) debe enviarse'], 422);
        }

        // Obtener cantidades desde el request (con valores predeterminados de 0)
        $cantidadFacil = $request->input('cantidadFacil', 0);
        $cantidadMedia = $request->input('cantidadMedia', 0);
        $cantidadDificil = $request->input('cantidadDificil', 0);

        // Verificar disponibilidad de preguntas
        $disponiblesFacil = QuestionBank::where('dificulty', 'facil')->count();
        $disponiblesMedio = QuestionBank::where('dificulty', 'medio')->count();
        $disponiblesDificil = QuestionBank::where('dificulty', 'dificil')->count();

        // Ajustar cantidades si no hay suficientes preguntas disponibles
        $cantidadFacil = min($cantidadFacil, $disponiblesFacil);
        $cantidadMedia = min($cantidadMedia, $disponiblesMedio);
        $cantidadDificil = min($cantidadDificil, $disponiblesDificil);

        // Obtener preguntas aleatorias según la dificultad especificada
        $preguntasFaciles = QuestionBank::where('dificulty', 'facil')
            ->inRandomOrder()
            ->take($cantidadFacil)
            ->get();

        $preguntasMedias = QuestionBank::where('dificulty', 'medio')
            ->inRandomOrder()
            ->take($cantidadMedia)
            ->get();

        $preguntasDificiles = QuestionBank::where('dificulty', 'dificil')
            ->inRandomOrder()
            ->take($cantidadDificil)
            ->get();

        // Combinar todas las preguntas en una colección
        $todasPreguntas = $preguntasFaciles->concat($preguntasMedias)
            ->concat($preguntasDificiles);

        return response()->json([
            'preguntas' => $todasPreguntas,
            'total' => $todasPreguntas->count(),
            'distribucion' => [
                'faciles' => $preguntasFaciles->count(),
                'medias' => $preguntasMedias->count(),
                'dificiles' => $preguntasDificiles->count()
            ],
            'disponibilidad' => [
                'faciles' => $disponiblesFacil,
                'medias' => $disponiblesMedio,
                'dificiles' => $disponiblesDificil
            ]
        ]);
    }

    public function assignRandomQuestions(ValidationQuestionEvaluation $request)
    {
        try {
            DB::beginTransaction();

            $evaluation = Evaluation::findOrFail($request->evaluation_id);
            $questionsPerArea = $request->questions_per_area;

            $assignedQuestions = [];
            $currentTotalScore = 0;

            foreach ($questionsPerArea as $areaId => $config) {
                $availableQuestions = QuestionBank::where('area_id', $areaId)->get();

                if ($availableQuestions->count() < $config['quantity']) {
                    throw new \Exception("No hay suficientes preguntas para el área $areaId");
                }

                $selectedQuestions = $availableQuestions->random($config['quantity']);
                $scorePerQuestion = $config['score'] / $config['quantity'];

                foreach ($selectedQuestions as $question) {
                    $assignedQuestion = new QuestionEvaluation();
                    $assignedQuestion->evaluation_id = $request->evaluation_id;
                    $assignedQuestion->question_id = $question->id;
                    $assignedQuestion->score = $scorePerQuestion;
                    $assignedQuestion->save();

                    $assignedQuestions[] = $assignedQuestion;

                    $currentTotalScore += $scorePerQuestion;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'assigned_questions' => $assignedQuestions,
                'total_score' => $currentTotalScore
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar preguntas: ' . $e->getMessage()
            ], 500);
        }
    }
}
