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
        $validationNumberPlaces = 'required|numeric|min:0';
        $validationCode = 'required|string|max:10|regex:/^[\pL\s\-]+$/u|unique:student_tests,code';
        $validationStartTime = 'required|date_format:H:i';
        $validationEndTime = 'required|date_format:H:i|after:StartTime';

        if ($this->route('id')) {
            $validationEvaluationId = 'required|exists:evaluations,id';
            $validationStudentId = 'required|exists:students,id';
            $validationNumberPlaces = 'required|numeric|min:0';
            $validationCode = 'required|string|max:10|regex:/^[\pL\s\-]+$/u|unique:student_tests,code,' . $this->route('id');
            $validationStartTime = 'required|date_format:H:i';
            $validationEndTime = 'required|date_format:H:i|after:StartTime';
        }
        return [
            'evaluation_id' => $validationEvaluationId,
            'student_id' => $validationStudentId,
            'number_places' => $validationNumberPlaces,
            'code' => $validationCode,
            'start_time' => $validationStartTime,
            'end_time' => $validationEndTime
        ];
    }

    public function messages()
    {
        return [
            'evaluation_id.required' => 'El campo id de evaluacion es obligatorio.',
            'evaluation_id.exists' => 'El campo Id de evaluacion no existe.',
            'student_id.required' => 'El campo Id de estudiante es obligatorio.',
            'student_id.exists' => 'El campo Id del estudiante no existe.',
            'number_places.required' => 'El campo numero de plazas es obligatorio.',
            'number_places.numeric' => 'El campo numero de plazas debe ser un número.',
            'number_places.min' => 'El campo numero de plazas debe ser mayor o igual a 0.',
            'code.required' => 'El campo code es obligatorio.',
            'code.string' => 'El campo code debe ser una cadena de texto.',
            'code.max' => 'El campo code no debe tener más de 10 caracteres.',
            'code.regex' => 'El campo code solo debe contener letras, espacios y guiones.',
            'code.unique' => 'El campo code debe ser único.',
            'start_time.required' => 'El campo hora de inicio es obligatorio.',
            'start_time.date_format' => 'El campo hora de inicio debe tener el formato H:i.',
            'end_time.required' => 'El campo hora final es obligatorio.',
            'end_time.date_format' => 'El campo hora final debe tener el formato H:i.',
            'end_time.after' => 'El campo hora final debe ser después del campo start_time.'
        ];
    }
}
