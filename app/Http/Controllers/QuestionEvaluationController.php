<?php

namespace App\Http\Controllers;

use App\Models\QuestionEvaluation;
use App\Models\QuestionBank;
use App\Models\Evaluation;
use App\Http\Requests\ValidationQuestionEvaluation;
use App\Models\Areas;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


class QuestionEvaluationController extends Controller
{
    public function CantidadPreguntas(Request $request)
    {
        // Validar entrada
        $request->validate([
            'area_id' => 'required|integer|exists:areas,id',
            'cantidadFacil' => 'nullable|integer|min:0',
            'cantidadMedia' => 'nullable|integer|min:0',
            'cantidadDificil' => 'nullable|integer|min:0',
        ]);

        $area_id = $request->input('area_id');

        // Obtener el área
        $area = Areas::find($area_id);

        if (!$request->has('cantidadFacil') && !$request->has('cantidadMedia') && !$request->has('cantidadDificil')) {
            return response()->json(['error' => 'Al menos uno de los parámetros (cantidadFacil, cantidadMedia, cantidadDificil) debe enviarse'], 422);
        }

        $cantidadFacil = $request->input('cantidadFacil', 0);
        $cantidadMedia = $request->input('cantidadMedia', 0);
        $cantidadDificil = $request->input('cantidadDificil', 0);

        // Filtrar por área
        $disponiblesFacil = QuestionBank::where('dificulty', 'facil')->where('area_id', $area_id)->count();
        $disponiblesMedio = QuestionBank::where('dificulty', 'medio')->where('area_id', $area_id)->count();
        $disponiblesDificil = QuestionBank::where('dificulty', 'dificil')->where('area_id', $area_id)->count();

        $cantidadFacil = min($cantidadFacil, $disponiblesFacil);
        $cantidadMedia = min($cantidadMedia, $disponiblesMedio);
        $cantidadDificil = min($cantidadDificil, $disponiblesDificil);

        $preguntasFaciles = QuestionBank::where('dificulty', 'facil')
            ->where('area_id', $area_id)
            ->inRandomOrder()
            ->take($cantidadFacil)
            ->get();

        $preguntasMedias = QuestionBank::where('dificulty', 'medio')
            ->where('area_id', $area_id)
            ->inRandomOrder()
            ->take($cantidadMedia)
            ->get();

        $preguntasDificiles = QuestionBank::where('dificulty', 'dificil')
            ->where('area_id', $area_id)
            ->inRandomOrder()
            ->take($cantidadDificil)
            ->get();

        $todasPreguntas = $preguntasFaciles->concat($preguntasMedias)->concat($preguntasDificiles);

        return response()->json([
            'area' => [
                'id' => $area->id,
                'nombre' => $area->name
            ],
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

    public function disponibles(Request $request)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id',
        ]);

        $areaId = $request->input('area_id');

        $facil = QuestionBank::where('area_id', $areaId)
            ->where('dificulty', 'facil')
            ->count();

        $media = QuestionBank::where('area_id', $areaId)
            ->where('dificulty', 'medio')
            ->count();

        $dificil = QuestionBank::where('area_id', $areaId)
            ->where('dificulty', 'dificil')
            ->count();

        return response()->json([
            'area_id' => (int)$areaId,
            'facil' => $facil,
            'media' => $media,
            'dificil' => $dificil
        ]);
    }
}
