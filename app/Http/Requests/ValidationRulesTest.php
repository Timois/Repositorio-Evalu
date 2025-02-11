<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidationRulesTest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $validationEvaluationId = 'required|exists:evaluations,id';
        $validationName = 'required|string|max:255|regex:/^[\pL\s,\-.]+$/u';
        $validationCode = 'required|string|max:10|regex:/^[\pL\s\-]+$/u|unique:rules_tests,code';
        $validationRangeTime = 'required|string|max:10|regex:/^[\pL\s\-]+$/u';
        $validationMinimunScore = 'required|numeric|min:0';
        $validationStatus = 'required|in:evaluado,creado,en_proceso';
        if ($this->route('id')) {
            $validationEvaluationId = 'required|exists:evaluations,id';
            $validationName = 'required|string|max:255|regex:/^[\pL\s,\-.]+$/u';
            $validationCode = 'required|string|max:10|regex:/^[\pL\s\-]+$/u|unique:rules_tests,code,' . $this->route('id');
            $validationRangeTime = 'required|string|max:10|regex:/^[\pL\s\-]+$/u';
            $validationMinimunScore = 'required|numeric|min:0';
            $validationStatus = 'required|in:evaluado,creado,en_proceso';
        }
        return [
            'evaluation_id' => $validationEvaluationId,
            'name' => $validationName,
            'code' => $validationCode,
            'range_time' => $validationRangeTime,
            'minimum_score' => $validationMinimunScore,
            'status' => $validationStatus
        ];
    }

    public function messages()
    {
        return [
            'evaluation_id.required' => 'El campo id de evaluacion es obligatorio.',
            'evaluation_id.exists' => 'El campo Id de evaluacion no existe.',
            'name.required' => 'El campo name es obligatorio.',
            'name.string' => 'El campo name debe ser una cadena de texto.',
            'name.max' => 'El campo name no debe tener más de 255 caracteres.',
            'name.regex' => 'El campo name solo debe contener letras, espacios, comas, guiones y puntos.',
            'code.required' => 'El campo code es obligatorio.',
            'code.string' => 'El campo code debe ser una cadena de texto.',
            'code.max' => 'El campo code no debe tener más de 10 caracteres.',
            'code.regex' => 'El campo code solo debe contener letras, espacios y guiones.',
            'code.unique' => 'El campo code debe ser único.',
            'range_time.required' => 'El campo range_time es obligatorio.',
            'range_time.string' => 'El campo range_time debe ser una cadena de texto.',
            'range_time.max' => 'El campo range_time no debe tener más de 10 caracteres.',
            'range_time.regex' => 'El campo range_time solo debe contener letras, espacios y guiones.',
            'minimum_score.required' => 'El campo minimum_score es obligatorio.',
            'minimum_score.numeric' => 'El campo minimum_score debe ser un número.',
            'minimum_score.min' => 'El campo minimum_score debe ser mayor o igual a 0.',
            'status.required' => 'El campo status es obligatorio.',
            'status.in' => 'El campo status debe ser "evaluado", "creado" o "en_proceso".',
        ];
    }
}
