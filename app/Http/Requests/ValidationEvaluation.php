<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidationEvaluation extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $evaluationId = $this->route('id'); // Obtener el ID de la evaluación desde la ruta

        // Definir reglas base
        $rules = [
            'academic_management_period_id' => 'required|exists:academic_management_period,id',
            'title' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'string',
                'max:255',
                'regex:/^[A-Za-zñ0-9\s]*$/',
                Rule::unique('evaluations', 'title')
                    ->where(function ($query) use ($evaluationId) {
                        // Obtener el academic_management_period_id del request o del modelo si es actualización
                        $periodId = $this->input('academic_management_period_id');

                        // Si es una actualización y no se envía academic_management_period_id,
                        // obtenerlo del modelo existente
                        if ($evaluationId && !$periodId) {
                            $evaluation = \App\Models\Evaluation::find($evaluationId);
                            $periodId = $evaluation ? $evaluation->academic_management_period_id : null;
                        }

                        return $query->where('academic_management_period_id', $periodId);
                    })
                    ->ignore($evaluationId, 'id'), // Ignorar el ID de la evaluación actual
            ],
            'description' => 'nullable|string|max:255|regex:/^[A-Za-zñ0-9\s]*$/',
            'passing_score' => 'required|numeric|min:0',
            'places' => 'required|integer|min:0',
            'date_of_realization' => 'required|date_format:Y-m-d|after_or_equal:today',
            'time' => 'required|integer',
        ];

        // Modificar reglas para actualización (PUT/PATCH)
        if ($evaluationId) {
            $rules['academic_management_period_id'] = 'sometimes|exists:academic_management_period,id';
            $rules['description'] = 'sometimes|string|max:255|regex:/^[A-Za-zñ0-9\s]*$/';
            $rules['passing_score'] = 'sometimes|numeric|min:0';
            $rules['places'] = 'sometimes|integer|min:0';
            $rules['date_of_realization'] = 'sometimes|date_format:Y-m-d|after_or_equal:today';
            $rules['time'] = 'sometimes|integer';
        }

        return $rules;
    }

    protected function prepareForValidation()
    {
        if ($this->has('title')) {
            $this->merge([
                'title' => strtoupper($this->title)
            ]);
        }
        if ($this->has('description')) {
            $this->merge([
                'description' => strtolower($this->description)
            ]);
        }
    }

    public function messages()
    {
        return [
            'title.unique' => 'El título ya existe para este periodo. Por favor, elige otro.',
            'title.required' => 'El título de la evaluación es obligatorio.',
            'title.string' => 'El título debe ser una cadena de texto.',
            'title.max' => 'El título no puede tener más de 20 caracteres.',
            'title.regex' => 'El título solo puede contener letras y espacios.',
            'academic_management_period_id.required' => 'El periodo académico es obligatorio.',
            'academic_management_period_id.exists' => 'El periodo académico seleccionado no existe.',
            'description.string' => 'La descripción debe ser una cadena de texto.',
            'description.max' => 'La descripción no puede tener más de 255 caracteres.',
            'description.regex' => 'La descripción solo puede contener letras y espacios.',
            'places.required' => 'El número de lugares es obligatorio.',
            'places.integer' => 'El número de lugares debe ser un número entero.',
            'places.min' => 'El número de lugares debe ser mayor o igual a 0.',
            'passing_score.required' => 'La puntuación mínima aprobatoria es obligatoria.',
            'passing_score.numeric' => 'La puntuación mínima aprobatoria debe ser un número.',
            'passing_score.min' => 'La puntuación mínima aprobatoria debe ser mayor a 0.',
            'date_of_realization.required' => 'La fecha de realización es obligatoria.',
            'date_of_realization.date_format' => 'La fecha de realización debe tener el formato Y-m-d.',
            'date_of_realization.after_or_equal' => 'La fecha de realización no puede ser anterior a hoy.',
            'date_of_realization.date' => 'La fecha de realización debe ser una fecha.',
            'time.required' => 'El tiempo es obligatorio.',
            'time.integer' => 'El tiempo debe ser un número entero.',
        ];
    }
}
