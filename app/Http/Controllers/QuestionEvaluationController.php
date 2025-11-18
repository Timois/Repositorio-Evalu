<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignQuestionRequest;
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

    public function asignQuestionsRandom(AssignQuestionRequest $request)
    {
        $evaluation = Evaluation::findOrFail($request->evaluation_id);
        $periodId = $evaluation->academic_management_period_id;

        $ponderar = $request->ponderar;
        $areas = $request->areas;
        $totalNota = collect($areas)->sum('nota');

        if (round($totalNota, 2) != round($evaluation->total_score, 2)) {
            return response()->json(['error' => 'La suma de notas por área debe coincidir con la nota total de la evaluación.'], 422);
        }

        try {
            DB::beginTransaction();
            foreach ($areas as $areaData) {
                $areaId = $areaData['id'];

                if ($ponderar) {
                    $cantFacil = $areaData['cantidadFacil'] ?? 0;
                    $cantMedio = $areaData['cantidadMedia'] ?? 0;
                    $cantDificil = $areaData['cantidadDificil'] ?? 0;

                    $dispFacil = $this->countQuestions($periodId, $areaId, 'facil');
                    $dispMedio = $this->countQuestions($periodId, $areaId, 'medio');
                    $dispDificil = $this->countQuestions($periodId, $areaId, 'dificil');

                    if ($dispFacil < $cantFacil || $dispMedio < $cantMedio || $dispDificil < $cantDificil) {
                        return response()->json([
                            'success' => false,
                            'message' => "No hay suficientes preguntas disponibles para el área."
                        ], 409);
                    }
                } else {
                    $cantidadTotal = $areaData['cantidadTotal'] ?? 0;
                    if ($cantidadTotal == 0) continue;

                    $dispTotal = $this->countQuestions($periodId, $areaId, null);

                    if ($dispTotal < $cantidadTotal) {
                        return response()->json([
                            'success' => false,
                            'message' => "No hay suficientes preguntas disponibles en el área."
                        ], 409);
                    }
                }
            }
            $studentTests = StudentTest::where('evaluation_id', $evaluation->id)->get();
            $asignadas = StudentTestQuestion::whereIn('student_test_id', $studentTests->pluck('id'))
                ->pluck('student_test_id')
                ->unique();

            if ($asignadas->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "Ya se asignaron preguntas antes."
                ], 409);
            }
            foreach ($studentTests as $studentTest) {
                $allForStudent = [];

                foreach ($areas as $areaData) {
                    $areaId = $areaData['id'];
                    $notaArea = $areaData['nota'];

                    if ($ponderar) {
                        $cantFacil = $areaData['cantidadFacil'] ?? 0;
                        $cantMedio = $areaData['cantidadMedia'] ?? 0;
                        $cantDificil = $areaData['cantidadDificil'] ?? 0;

                        $totalPreg = $cantFacil + $cantMedio + $cantDificil;
                        if ($totalPreg == 0) continue;
                        $puntaje = $notaArea / $totalPreg;
                        $pf = $this->getQuestionsByDifficulty($periodId, $areaId, 'facil', $cantFacil);
                        $pm = $this->getQuestionsByDifficulty($periodId, $areaId, 'medio', $cantMedio);
                        $pd = $this->getQuestionsByDifficulty($periodId, $areaId, 'dificil', $cantDificil);

                        $preguntas = $pf->concat($pm)->concat($pd)->shuffle();
                    } else {
                        $cantidadTotal = $areaData['cantidadTotal'];
                        if ($cantidadTotal == 0) continue;

                        $puntaje = $notaArea / $cantidadTotal;

                        $preguntas = $this->getQuestions($periodId, $areaId, $cantidadTotal);
                    }

                    foreach ($preguntas as $pregunta) {
                        $allForStudent[] = [
                            'student_test_id' => $studentTest->id,
                            'question_id' => $pregunta->id,
                            'score_assigned' => $puntaje,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        $pregunta->update(['status' => 'inactivo']);
                    }
                }

                StudentTestQuestion::insert($allForStudent);

                $studentTest->questions_order = collect($allForStudent)->pluck('question_id')->toJson();
                $studentTest->save();
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Preguntas asignadas correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    private function countQuestions($periodId, $areaId, $difficulty = null)
    {
        return QuestionBank::where('area_id', $areaId)
            ->when($difficulty, fn($q) => $q->where('dificulty', $difficulty))
            ->where('status', 'activo')
            ->whereIn('id', function ($q) use ($periodId) {
                $q->select('bank_question_id')
                    ->from('academic_management_period_bank_question')
                    ->where('academic_management_period_id', $periodId);
            })
            ->count();
    }
    private function getQuestionsByDifficulty($periodId, $areaId, $difficulty, $quantity)
    {
        return QuestionBank::where('area_id', $areaId)
            ->where('dificulty', $difficulty)
            ->where('status', 'activo') // solo activas
            ->whereIn('id', function ($q) use ($periodId) {
                $q->select('bank_question_id')
                    ->from('academic_management_period_bank_question')
                    ->where('academic_management_period_id', $periodId);
            })
            ->inRandomOrder()
            ->take($quantity)
            ->get();
    }
    private function getQuestions($periodId, $areaId, $quantity)
    {
        $active = QuestionBank::where('area_id', $areaId)
            ->where('status', 'activo')
            ->whereIn('id', function ($q) use ($periodId) {
                $q->select('bank_question_id')
                    ->from('academic_management_period_bank_question')
                    ->where('academic_management_period_id', $periodId);
            })
            ->inRandomOrder()
            ->take($quantity)
            ->get();

        if ($active->count() < $quantity) {
            $remaining = $quantity - $active->count();

            $inactive = QuestionBank::where('area_id', $areaId)
                ->where('status', 'inactivo')
                ->whereIn('id', function ($q) use ($periodId) {
                    $q->select('bank_question_id')
                        ->from('academic_management_period_bank_question')
                        ->where('academic_management_period_id', $periodId);
                })
                ->inRandomOrder()
                ->take($remaining)
                ->get();

            return $active->concat($inactive);
        }

        return $active;
    }

    public function completeStudentTest($studentTestId)
    {
        try {
            DB::beginTransaction();

            $studentTest = StudentTest::findOrFail($studentTestId);

            // Verificar si el student_test ya está finalizado para evitar duplicados
            if ($studentTest->status === 'completado') {
                throw new \Exception('El examen ya está finalizado.');
            }

            // Marcar el student_test como completado
            $studentTest->update(['status' => 'completado']);

            // Obtener todas las preguntas asignadas al student_test
            $assignedQuestions = StudentTestQuestion::where('student_test_id', $studentTest->id)
                ->pluck('question_id');

            // Reactivar las preguntas asignadas
            QuestionBank::whereIn('id', $assignedQuestions)
                ->update(['status' => 'activo']);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Examen finalizado y preguntas reactivadas.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function disponibles(Request $request)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'evaluation_id' => 'required|exists:evaluations,id',
        ]);

        $areaId = $request->input('area_id');
        $evaluation = Evaluation::findOrFail($request->evaluation_id);

        $periodId = $evaluation->academic_management_period_id;

        $baseQuery = QuestionBank::where('area_id', $areaId)
            ->where('status', 'activo')
            ->whereIn('id', function ($query) use ($periodId) {
                $query->select('bank_question_id')
                    ->from('academic_management_period_bank_question')
                    ->where('academic_management_period_id', $periodId);
            });

        $facil = (clone $baseQuery)->where('dificulty', 'facil')->count();
        $media = (clone $baseQuery)->where('dificulty', 'medio')->count();
        $dificil = (clone $baseQuery)->where('dificulty', 'dificil')->count();

        return response()->json([
            'area_id' => (int)$areaId,
            'period_id' => $periodId,
            'facil' => $facil,
            'media' => $media,
            'dificil' => $dificil,
            'total' => $facil + $media + $dificil,
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

    // Verificar si las preguntas ya estan asignadas a los estudiantes de esa evaluacion
    public function findByEvaluationId(string $id)
    {
        $evaluation = Evaluation::find($id);

        if (!$evaluation) {
            return response()->json(['error' => 'Evaluación no encontrada'], 404);
        }

        // Obtener todos los student_test_id relacionados con esta evaluación
        $studentTestIds = StudentTest::where('evaluation_id', $evaluation->id)->pluck('id');

        // Verificar si existe al menos una pregunta asignada a alguno de esos student_test_id
        $questionsAssigned = StudentTestQuestion::whereIn('student_test_id', $studentTestIds)->exists();

        return response()->json([
            'status' => $questionsAssigned,
            'message' => $questionsAssigned
                ? 'Preguntas ya asignadas a la evaluación'
                : 'No hay preguntas asignadas a la evaluación',
        ]);
    }

    public function activeQuestions(Request $request, string $areaId)
    {
        $request->validate([
            'evaluation_id' => 'required|exists:evaluations,id'
        ]);

        $evaluation = Evaluation::findOrFail($request->evaluation_id);

        $periodId = $evaluation->academic_management_period_id;

        $area = Areas::find($areaId);
        if (!$area) {
            return response()->json(['error' => 'El área no existe'], 404);
        }

        $preguntasDelPeriodo = DB::table('academic_management_period_bank_question')
            ->where('academic_management_period_id', $periodId)
            ->pluck('bank_question_id');

        if ($preguntasDelPeriodo->isEmpty()) {
            return response()->json([
                'message' => 'El periodo no tiene preguntas registradas.'
            ], 404);
        }

        $updated = QuestionBank::where('area_id', $areaId)
            ->whereIn('id', $preguntasDelPeriodo)
            ->where('status', 'inactivo')
            ->update(['status' => 'activo']);

        if ($updated === 0) {
            return response()->json([
                'message' => 'No hay preguntas inactivas en este periodo para esta área.'
            ], 200);
        }

        return response()->json([
            'message' => 'Preguntas activadas correctamente.',
            'preguntas_activadas' => $updated
        ], 200);
    }
}
