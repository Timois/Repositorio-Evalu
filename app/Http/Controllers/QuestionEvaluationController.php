<?php

namespace App\Http\Controllers;

use App\Models\QuestionEvaluation;
use App\Models\QuestionBank;
use App\Models\Evaluation;
use App\Http\Requests\ValidationQuestionEvaluation;
use Illuminate\Support\Facades\DB;

class QuestionEvaluationController extends Controller
{
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