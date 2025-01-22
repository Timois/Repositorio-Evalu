<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidationAnswerBank extends FormRequest
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
        $validationAnswer = 'required|string|max:255|regex:/^[\pL\s,\-.\d\(\)\[\]\+\*\/=\^_°√]+$/u|unique:bank_answers,answer';
        $validationWeight = 'required|numeric|min:0';
        $validationIsCorrect = 'required|in:false,true';
        $validationStatus = 'required|in:activo,inactivo';
        $answerId = $this->route("id");
        if ($answerId) {
            $validationAnswer = 'required|string|max:255|regex:/^[\pL\s,\-.\d\(\)\[\]\+\*\/=\^_°√]+$/u|unique:bank_answers,answer,' . $answerId;
            $validationWeight = 'required|numeric|min:0';
            $validationIsCorrect = 'required|in:false,true';
            $validationStatus = 'required|in:activo,inactivo';
        }
        return [
            'answer' => $validationAnswer, 
            'weight' => $validationWeight,
            'is_correct' => $validationIsCorrect,
            'status' => $validationStatus
        ];
    }
    public function messages()
    {
        return [
            'answer.required' => 'La respuesta es requerida.',
            'answer.string' => 'La respuesta debe ser una cadena de texto.',
            'answer.max' => 'La longitud máxima de la respuesta es de 255 caracteres.',
            'answer.regex' => 'La respuesta debe contener solo letras y espacios.',
            'answer.unique' => 'La respuesta ya existe en la base de datos.',
            'weight.required' => 'El peso es requerido.',
            'weight.numeric' => 'El peso debe ser un número.',
            'weight.min' => 'El peso debe ser mayor o igual a 0.',
            'is_correct.required' => 'El campo "correcta" es requerido.',
            'is_correct.in' => 'El campo "correcta" debe ser "false" o "true".',
            'status.required' => 'El campo "estado" es requerido.',
            'status.in' => 'El campo "estado" debe ser "activo" o "inactivo".',
        ];
    }
}
