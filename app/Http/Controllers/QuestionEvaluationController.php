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
        $ponderar = $request->ponderar;
        $areas = $request->areas;
        $totalNota = collect($areas)->sum('nota');

        if (round($totalNota, 2) != round($evaluation->total_score, 2)) {
            return response()->json(['error' => 'La suma de notas por área debe coincidir con la nota total de la evaluación.'], 422);
        }

        try {
            DB::beginTransaction();
            // verificar disponibilidad de preguntas
            foreach ($areas as $areaData) {
                $areaId = $areaData['id'];
                if ($ponderar) {
                    $cantidadFacil = $areaData['cantidadFacil'] ?? 0;
                    $cantidadMedia = $areaData['cantidadMedia'] ?? 0;
                    $cantidadDificil = $areaData['cantidadDificil'] ?? 0;
                    $totalPreguntas = $cantidadFacil + $cantidadMedia + $cantidadDificil;
                    if ($totalPreguntas == 0) continue;
                    $preguntasDisponiblesFacil = QuestionBank::where('area_id', $areaId)
                        ->where('dificulty', 'facil')
                        ->count();
                    $preguntasDisponiblesMedia = QuestionBank::where('area_id', $areaId)
                        ->where('dificulty', 'medio')
                        ->count();
                    $preguntasDisponiblesDificil = QuestionBank::where('area_id', $areaId)
                        ->where('dificulty', 'dificil')
                        ->count();
                    if ($preguntasDisponiblesFacil < $cantidadFacil || $preguntasDisponiblesMedia < $cantidadMedia || $preguntasDisponiblesDificil < $cantidadDificil) {
                        return response()->json([
                            'success' => false,
                            'message' => "No hay suficientes preguntas disponibles para esta área."
                        ], 409);
                    }
                } else {
                    $cantidadTotal = $areaData['cantidadTotal'] ?? 0;
                    if ($cantidadTotal == 0) continue;
                    $preguntasDisponibles = QuestionBank::where('area_id', $areaId)
                        ->count();
                    if ($preguntasDisponibles < $cantidadTotal) {
                        return response()->json([
                            'success' => false,
                            'message' => "No hay suficientes preguntas disponibles para esta área."
                        ], 409);
                    }   
                }
            }
            // Obtener todos los student_tests de esta evaluación
            $studentTests = StudentTest::where('evaluation_id', $evaluation->id)->get();
            $studentTestsConPreguntas = StudentTestQuestion::whereIn('student_test_id', $studentTests->pluck('id'))->pluck('student_test_id')->unique();

            if ($studentTestsConPreguntas->isNotEmpty()) {
                $studentTestsConPreguntas->implode(', ');
                return response()->json([
                    'success' => false,
                    'message' => "Ya se asignaron preguntas a los estudiantes. No se puede volver a asignar."
                ], 409);
            }

            foreach ($studentTests as $studentTest) {
                $allQuestionsForStudent = [];

                foreach ($areas as $areaData) {
                    $areaId = $areaData['id'];
                    $notaArea = $areaData['nota'];

                    if ($ponderar) {
                        $cantidadFacil = $areaData['cantidadFacil'] ?? 0;
                        $cantidadMedia = $areaData['cantidadMedia'] ?? 0;
                        $cantidadDificil = $areaData['cantidadDificil'] ?? 0;
                        $totalPreguntas = $cantidadFacil + $cantidadMedia + $cantidadDificil;

                        if ($totalPreguntas == 0) continue;

                        // Verificar disponibilidad de preguntas (activas e inactivas)
                        $preguntasDisponiblesFacil = QuestionBank::where('area_id', $areaId)
                            ->where('dificulty', 'facil')
                            ->count();
                        $preguntasDisponiblesMedia = QuestionBank::where('area_id', $areaId)
                            ->where('dificulty', 'medio')
                            ->count();
                        $preguntasDisponiblesDificil = QuestionBank::where('area_id', $areaId)
                            ->where('dificulty', 'dificil')
                            ->count();

                        if (
                            $preguntasDisponiblesFacil < $cantidadFacil ||
                            $preguntasDisponiblesMedia < $cantidadMedia ||
                            $preguntasDisponiblesDificil < $cantidadDificil
                        ) {
                            throw new \Exception("No hay suficientes preguntas (activas o inactivas) disponibles para el área {$areaId}.");
                        }

                        $puntajePorPregunta = $notaArea / $totalPreguntas;

                        // Seleccionar preguntas por dificultad
                        $preguntasFacil = $this->getQuestionsByDifficulty($areaId, 'facil', $cantidadFacil);
                        $preguntasMedia = $this->getQuestionsByDifficulty($areaId, 'medio', $cantidadMedia);
                        $preguntasDificil = $this->getQuestionsByDifficulty($areaId, 'dificil', $cantidadDificil);

                        $preguntas = $preguntasFacil->concat($preguntasMedia)->concat($preguntasDificil)->shuffle();
                    } else {
                        $totalPreguntas = $areaData['cantidadTotal'];
                        if ($totalPreguntas == 0) continue;

                        // Verificar disponibilidad total de preguntas
                        $preguntasDisponibles = QuestionBank::where('area_id', $areaId)->count();
                        if ($preguntasDisponibles < $totalPreguntas) {
                            throw new \Exception("No hay suficientes preguntas (activas o inactivas) disponibles para el área {$areaId}.");
                        }

                        $puntajePorPregunta = $notaArea / $totalPreguntas;

                        // Seleccionar preguntas sin considerar dificultad
                        $preguntas = $this->getQuestions($areaId, $totalPreguntas);
                    }

                    foreach ($preguntas as $pregunta) {
                        $allQuestionsForStudent[] = [
                            'student_test_id' => $studentTest->id,
                            'question_id' => $pregunta->id,
                            'score_assigned' => round($puntajePorPregunta, 2),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        // Marcar la pregunta como inactiva
                        $pregunta->update(['status' => 'inactivo']);
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

    private function getQuestionsByDifficulty($areaId, $difficulty, $quantity)
    {
        // Primero intentar obtener preguntas activas
        $activeQuestions = QuestionBank::where('area_id', $areaId)
            ->where('dificulty', $difficulty)
            ->where('status', 'activo')
            ->inRandomOrder()
            ->take($quantity)
            ->get();

        // Si no hay suficientes preguntas activas, complementar con inactivas
        if ($activeQuestions->count() < $quantity) {
            $remainingQuantity = $quantity - $activeQuestions->count();
            $inactiveQuestions = QuestionBank::where('area_id', $areaId)
                ->where('dificulty', $difficulty)
                ->where('status', 'inactivo')
                ->inRandomOrder()
                ->take($remainingQuantity)
                ->get();

            return $activeQuestions->concat($inactiveQuestions);
        }

        return $activeQuestions;
    }

    private function getQuestions($areaId, $quantity)
    {
        // Primero intentar obtener preguntas activas
        $activeQuestions = QuestionBank::where('area_id', $areaId)
            ->where('status', 'activo')
            ->inRandomOrder()
            ->take($quantity)
            ->get();

        // Si no hay suficientes preguntas activas, complementar con inactivas
        if ($activeQuestions->count() < $quantity) {
            $remainingQuantity = $quantity - $activeQuestions->count();
            $inactiveQuestions = QuestionBank::where('area_id', $areaId)
                ->where('status', 'inactivo')
                ->inRandomOrder()
                ->take($remainingQuantity)
                ->get();

            return $activeQuestions->concat($inactiveQuestions);
        }

        return $activeQuestions;
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
        ]);
        $areaId = $request->input('area_id');

        $facil = QuestionBank::where('area_id', $areaId)
            ->where('dificulty', 'facil')
            ->where('status', 'activo')
            ->count();

        $media = QuestionBank::where('area_id', $areaId)
            ->where('dificulty', 'medio')
            ->where('status', 'activo')
            ->count();

        $dificil = QuestionBank::where('area_id', $areaId)
            ->where('dificulty', 'dificil')
            ->where('status', 'activo')
            ->count();

        return response()->json([
            'area_id' => (int)$areaId,
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

    public function activeQuestions(string $id)
    {
        $area = Areas::find($id);

        if (!$area) {
            return response()->json(['error' => 'El área no existe'], 404);
        }

        // Activar todas las preguntas inactivas del área
        $updated = QuestionBank::where('area_id', $id)
            ->where('status', 'inactivo')
            ->update(['status' => 'activo']);

        if ($updated === 0) {
            return response()->json([
                'message' => 'No hay preguntas inactivas en esta área'
            ], 200);
        }

        return response()->json([
            'message' => 'Preguntas activadas correctamente',
            'preguntas_activadas' => $updated
        ], 200);
    }
}
