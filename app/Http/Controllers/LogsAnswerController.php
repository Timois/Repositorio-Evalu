<?php

namespace App\Http\Controllers;

use App\Models\LogsAnswer;
use App\Models\Student;
use App\Models\StudentTest;
use App\Models\StudentTestQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions\StudentT;

class LogsAnswerController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'student_test_id' => 'required|exists:student_tests,id',
            'question_id' => 'required|exists:bank_questions,id',
            'answer_id' => 'nullable|integer',
            'time' => 'nullable|string' // formato HH:MM:SS
        ]);

        try {
            DB::beginTransaction();

            // Buscar la pregunta de ese test
            $studentQuestion = StudentTestQuestion::where('student_test_id', $request->student_test_id)
                ->where('question_id', $request->question_id)
                ->first();

            if (!$studentQuestion) {
                return response()->json(['error' => 'Pregunta no encontrada para este examen'], 404);
            }

            // Actualizar la respuesta actual del estudiante
            $studentQuestion->update([
                'student_answer' => $request->student_answer,
                'is_correct' => null // Se puede calcular después
            ]);

            // Marcar los logs anteriores como no últimos
            LogsAnswer::where('student_test_question_id', $studentQuestion->id)
                ->update(['is_ultimate' => false]);

            // Guardar nuevo log
            LogsAnswer::create([
                'student_test_id' => $request->student_test_id,
                'student_test_question_id' => $studentQuestion->id,
                'answer_id' => $request->answer_id,
                'time' => $request->time ?? now()->format('H:i:s'),
                'is_ultimate' => true
            ]);

            DB::commit();

            return response()->json(['message' => 'Respuesta guardada correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al guardar la respuesta.', 'details' => $e->getMessage()], 500);
        }
    }

    // Recuperar las respuestas ya respondidas por el estudiante
    public function getAnswers($id)
    {
        // Buscar el StudentTest por ID
        $studentTest = StudentTest::find($id);

        // Verificar si el registro existe
        if (!$studentTest) {
            return response()->json(['message' => 'StudentTest no encontrado'], 404);
        }

        // Recuperar las respuestas asociadas al student_test_id
        $answers = LogsAnswer::where('student_test_id', $id)->get();

        return response()->json($answers);
    }
}
