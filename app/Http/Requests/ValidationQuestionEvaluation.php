<?php

namespace App\Http\Requests;

use App\Models\Evaluation;
use App\Models\QuestionBank;
use Illuminate\Foundation\Http\FormRequest;

class ValidationQuestionEvaluation extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evaluation_id' => 'required|exists:evaluations,id',
            'questions_per_area' => 'required|array',
            'questions_per_area.*.quantity' => 'required|integer|min:1',
            'questions_per_area.*.score' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'questions_per_area.required' => 'Debe especificar la configuración de preguntas por área',
            'questions_per_area.*.quantity.required' => 'Debe especificar la cantidad de preguntas para cada área',
            'questions_per_area.*.score.required' => 'Debe especificar el puntaje para cada área',
        ];
    }
}