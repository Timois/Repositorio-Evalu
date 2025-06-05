<?php

namespace App\Http\Controllers;

use App\Models\QuestionEvaluation;
use App\Models\QuestionBank;
use App\Models\Evaluation;
use App\Http\Requests\ValidationQuestionEvaluation;
use App\Models\Areas;
use App\Models\StudentTest;
use App\Models\StudentTestQuestion;
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
        $area = Areas::where('id', $area_id);
        if (!$area) {
            return response()->json(['error' => 'El área no existe'], 404);
        }
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

    public function asignQuestionsRandom(Request $request)
    {
        $request->validate([
            'evaluation_id' => 'required|exists:evaluations,id',
            'areas' => 'required|array',
            'areas.*.id' => 'required|exists:areas,id',
            'areas.*.nota' => 'required|numeric|min:0',
            'areas.*.cantidadFacil' => 'nullable|integer|min:0',
            'areas.*.cantidadMedia' => 'nullable|integer|min:0',
            'areas.*.cantidadDificil' => 'nullable|integer|min:0',
        ]);

        $evaluation = Evaluation::findOrFail($request->evaluation_id);
        
        $areas = $request->areas;
        $totalNota = collect($areas)->sum('nota');

        if (round($totalNota, 2) != round($evaluation->total_score, 2)) {
            return response()->json(['error' => 'La suma de notas por área debe coincidir con la nota total de la evaluación.'], 422);
        }

        try {
            DB::beginTransaction();

            // Obtener todos los student_tests de esta evaluación
            $studentTests = StudentTest::where('evaluation_id', $evaluation->id)->get();

            foreach ($studentTests as $studentTest) {
                $allQuestionsForStudent = [];

                foreach ($areas as $areaData) {
                    $areaId = $areaData['id'];
                    $notaArea = $areaData['nota'];
                    $cantidadFacil = $areaData['cantidadFacil'] ?? 0;
                    $cantidadMedia = $areaData['cantidadMedia'] ?? 0;
                    $cantidadDificil = $areaData['cantidadDificil'] ?? 0;

                    $totalPreguntas = $cantidadFacil + $cantidadMedia + $cantidadDificil;
                    if ($totalPreguntas == 0) continue;

                    $puntajePorPregunta = $notaArea / $totalPreguntas;

                    $preguntasFacil = QuestionBank::where('area_id', $areaId)->where('dificulty', 'facil')->inRandomOrder()->take($cantidadFacil)->get();
                    $preguntasMedia = QuestionBank::where('area_id', $areaId)->where('dificulty', 'medio')->inRandomOrder()->take($cantidadMedia)->get();
                    $preguntasDificil = QuestionBank::where('area_id', $areaId)->where('dificulty', 'dificil')->inRandomOrder()->take($cantidadDificil)->get();

                    $preguntas = $preguntasFacil->concat($preguntasMedia)->concat($preguntasDificil)->shuffle();

                    foreach ($preguntas as $index => $pregunta) {
                        $allQuestionsForStudent[] = [
                            'student_test_id' => $studentTest->id,
                            'question_id' => $pregunta->id,
                            'score_assigned' => round($puntajePorPregunta, 2),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                // Insertar todas las preguntas para el estudiante
                StudentTestQuestion::insert($allQuestionsForStudent);

                // Guardar el orden para la tabla student_tests.questions_order
                $studentTest->questions_order = collect($allQuestionsForStudent)->pluck('question_id')->toJson();
                $studentTest->save();
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Preguntas asignadas correctamente a todos los estudiantes.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
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
    public function find()
    {
        $questions = QuestionEvaluation::orderBy('id', 'ASC')->get();
        return response()->json($questions);
    }

    public function findById(string $id)
    {
        $question = QuestionEvaluation::where('evaluation_id', $id)->get();
        if (!$question)
            return ["message:", "La pregunta con id:" . $id . " no existe."];
        return response()->json($question);
    }
}
