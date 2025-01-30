<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        $validationTitle = 'required|string|max:255|regex:/^[\pL\s,\-.\d]+$/u';
        $validationdescription = 'string|max:255|regex:/^[\pL\s,\-.\d]+$/u';
        $validationNumberQuestions = 'required|numeric|min:0';
        $validationTotalScore = 'required|numeric|min:0';
        $validationIsRandom = 'required|in:true,false';
        $validationDuration = [
            'required',
            'regex:/^\d{2}:\d{2}:\d{2}$/',
            function ($attribute, $value, $fail) {
                $parts = explode(':', $value);
                if (
                    count($parts) !== 3 ||
                    $parts[0] > 23 ||
                    $parts[1] > 59 ||
                    $parts[2] > 59
                ) {
                    $fail('The duration must be a valid time format (HH:MM:SS)');
                }
            }
        ];
        $validationAcademicPeriod = 'required|exists:academic_management_period,id';
        $validationStatus = 'required|in:activo,inactivo';
        $validationType = 'required|in:web,ocr,app';
        $evaluation = $this->route("id");

        if ($evaluation) {
            $validationTitle = 'required|string|max:255|regex:/^[\pL\s,\-.\d]+$/u' . $evaluation;
            $validationdescription = 'string|max:255|regex:/^[\pL\s,\-.\d]+$/u';
            $validationNumberQuestions = 'required|numeric|min:0';
            $validationTotalScore = 'required|numeric|min:0';
            $validationIsRandom = 'required|in:true,false';
            $validationDuration = [
                'required',
                'regex:/^\d{2}:\d{2}:\d{2}$/',
                function ($attribute, $value, $fail) {
                    $parts = explode(':', $value);
                    if (
                        count($parts) !== 3 ||
                        $parts[0] > 23 ||
                        $parts[1] > 59 ||
                        $parts[2] > 59
                    ) {
                        $fail('The duration must be a valid time format (HH:MM:SS)');
                    }
                }
            ];
            $validationAcademicPeriod = 'required|exists:academic_management_period,id';
            $validationStatus = 'required|in:activo,inactivo';
            $validationType = 'required|in:web,ocr,app';
        }

        return [
            'title' => $validationTitle,
            'description' => $validationdescription,
            'number_questions' => $validationNumberQuestions,
            'total_score' => $validationTotalScore,
            'is_random' => $validationIsRandom,
            'duration' => $validationDuration,
            'academic_management_period_id' => $validationAcademicPeriod,
            'status' => $validationStatus,
            'type' => $validationType
        ];
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
            'title.required' => 'El campo título es obligatorio.',
            'title.string' => 'El campo título debe ser una cadena de texto.',
            'title.max' => 'La longitud máxima del título es de 255 caracteres.',
            'title.regex' => 'El título debe contener solo letras y espacios.',
            'description.string' => 'El campo descripción debe ser una cadena de texto.',
            'description.max' => 'La longitud máxima de la descripción es de 255 caracteres.',
            'description.regex' => 'La descripción debe contener solo letras y espacios.',
            'number_questions.required' => 'El campo número de preguntas es obligatorio.',
            'number_questions.numeric' => 'El campo número de preguntas debe ser un número.',
            'number_questions.min' => 'El campo número de preguntas debe ser mayor o igual a 0.',
            'total_score.required' => 'El campo puntaje total es obligatorio.',
            'total_score.numeric' => 'El campo puntaje total debe ser un número.',
            'total_score.min' => 'El campo puntaje total debe ser mayor o igual a 0.',
            'is_random.required' => 'El campo aleatorio es obligatorio.',
            'is_random.in' => 'El campo is_random debe ser "false" o "true".',
            'duration.required' => 'La duracion de la evaluacion es obligatorio',
            'duration.regex' =>  'La duracion debe ser una hora en este formato h:m:s',
            'academic_management_period_id.required' => 'El periodo asignado a la gestion academica es obligatorio',
            'academic_management_period_id.exists' => 'El periodo asignado a la gestion academica no existe',
            'status.required' => 'El campo estado es obligatorio.',
            'status.in' => 'El campo estado debe ser "activo" o "inactivo".',
            'type.required' => 'El campo tipo es obligatorio.',
            'type.in' => 'El campo tipo debe ser "web", "ocr" o "app".',
        ];
    }
}
