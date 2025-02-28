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
        $validationTotalScore = 'required|numeric|min:0';
        $validationPassingScore = 'required|numeric|min:0';
        $validationDateRealization = 'required|date_format:Y-m-d|after:today';
        $validationAcademicPeriod = 'required|exists:academic_management_period,id';
        $validationStatus = 'required|in:activo,inactivo';
        $validationType = 'required|in:web,ocr,app';
        $evaluation = $this->route("id");

        if ($evaluation) {
            $validationTitle = 'required|string|max:255|regex:/^[\pL\s,\-.\d]+$/u' . $evaluation;
            $validationdescription = 'string|max:255|regex:/^[\pL\s,\-.\d]+$/u';
            $validationTotalScore = 'required|numeric|min:0';
            $validationPassingScore = 'required|numeric|min:0';
            $validationDateRealization = 'required|date_format:Y-m-d';
            $validationAcademicPeriod = 'required|exists:academic_management_period,id';
            $validationStatus = 'required|in:activo,inactivo';
            $validationType = 'required|in:web,ocr,app';
        }

        return [
            'title' => $validationTitle,
            'description' => $validationdescription,
            'total_score' => $validationTotalScore,
            'passing_score' => $validationPassingScore,
            'date_of_realization' => $validationDateRealization,
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
            'total_score.required' => 'El campo puntaje total es obligatorio.',
            'total_score.numeric' => 'El campo puntaje total debe ser un número.',
            'total_score.min' => 'El campo puntaje total debe ser mayor o igual a 0.',
            'passing_score.required' => 'El campo puntaje de aprobación es obligatorio.',
            'passing_score.numeric' => 'El campo puntaje de aprobación debe ser un número.',
            'passing_score.min' => 'El campo puntaje de aprobación debe ser mayor o igual a 0.',
            'date_of_realization.required' => 'El campo fecha de realización es obligatorio.',
            'date_of_realization.date_format' => 'El campo fecha de realización debe tener el formato Y-m-d.',
            'date_of_realization.after' => 'El campo fecha de realización debe ser después de la fecha actual.',
            'academic_management_period_id.required' => 'El periodo asignado a la gestion academica es obligatorio',
            'academic_management_period_id.exists' => 'El periodo asignado a la gestion academica no existe',
            'status.required' => 'El campo estado es obligatorio.',
            'status.in' => 'El campo estado debe ser "activo" o "inactivo".',
            'type.required' => 'El campo tipo es obligatorio.',
            'type.in' => 'El campo tipo debe ser "web", "ocr" o "app".',
        ];
    }
}
