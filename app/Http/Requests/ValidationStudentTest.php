<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidationStudentTest extends FormRequest
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
        $validationEvaluationId = 'required|exists:evaluations,id';
        $validationStudentId = 'required|exists:students,id';
        $validationCode = 'required|string|max:10|regex:/^[\pL\s\-]+$/u|unique:student_tests,code';
        $validationStartTime = 'required|date_format:H:i';
        $validationEndTime = 'required|date_format:H:i|after:StartTime';
        $validationScoreObtained = 'required|numeric|min:0';
        $validationCorrectAnswers = 'required|numeric|min:0';
        $validationIncorrectAnswers = 'required|numeric|min:0';
        $validationNotAnswered = 'required|numeric|min:0';
        if ($this->route('id')) {
            $validationEvaluationId = 'required|exists:evaluations,id';
            $validationStudentId = 'required|exists:students,id';
            $validationCode = 'required|string|max:10|regex:/^[\pL\s\-]+$/u|unique:student_tests,code,' . $this->route('id');
            $validationStartTime = 'required|date_format:H:i';
            $validationEndTime = 'required|date_format:H:i|after:StartTime';
            $validationScoreObtained = 'required|numeric|min:0';
            $validationCorrectAnswers = 'required|numeric|min:0';
            $validationIncorrectAnswers = 'required|numeric|min:0';
            $validationNotAnswered = 'required|numeric|min:0';
        }
        return [
            'evaluation_id' => $validationEvaluationId,
            'student_id' => $validationStudentId,
            'code' => $validationCode,
            'start_time' => $validationStartTime,
            'end_time' => $validationEndTime,
            'score_obtained' => $validationScoreObtained,
            'correct_answers' => $validationCorrectAnswers,
            'incorrect_answers' => $validationIncorrectAnswers,
            'not_answered' => $validationNotAnswered
        ];
    }

    public function messages()
    {
        return [
            'evaluation_id.required' => 'El campo id de evaluacion es obligatorio.',
            'evaluation_id.exists' => 'El campo Id de evaluacion no existe.',
            'student_id.required' => 'El campo Id de estudiante es obligatorio.',
            'student_id.exists' => 'El campo Id del estudiante no existe.',
            'code.required' => 'El campo code es obligatorio.',
            'code.string' => 'El campo code debe ser una cadena de texto.',
            'code.max' => 'El campo code no debe tener más de 10 caracteres.',
            'code.regex' => 'El campo code solo debe contener letras, espacios y guiones.',
            'code.unique' => 'El campo code debe ser único.',
            'start_time.required' => 'El campo hora de inicio es obligatorio.',
            'start_time.date_format' => 'El campo hora de inicio debe tener el formato H:i.',
            'end_time.required' => 'El campo hora final es obligatorio.',
            'end_time.date_format' => 'El campo hora final debe tener el formato H:i.',
            'end_time.after' => 'El campo hora final debe ser después del campo start_time.',
            'score_obtained.required' => 'El campo puntaje obtenido es obligatorio.',
            'score_obtained.numeric' => 'El campo puntaje obtenido debe ser un número.',
            'score_obtained.min' => 'El campo puntaje obtenido no puede ser menor a 0.',
            'correct_answers.required' => 'El campo respuestas correctas es obligatorio.',
            'correct_answers.numeric' => 'El campo respuestas correctas debe ser un número.',
            'correct_answers.min' => 'El campo respuestas correctas no puede ser menor a 0.',
            'incorrect_answers.required' => 'El campo respuestas incorrectas es obligatorio.',
            'incorrect_answers.numeric' => 'El campo respuestas incorrectas debe ser un número.',
            'incorrect_answers.min' => 'El campo respuestas incorrectas no puede ser menor a 0.',
            'not_answered.required' => 'El campo no respondidas es obligatorio.',
            'not_answered.numeric' => 'El campo no respondidas debe ser un número.',
            'not_answered.min' => 'El campo no respondidas no puede ser menor a 0.',
        ];
    }
}
